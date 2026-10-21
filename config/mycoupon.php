<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ตรวจสอบการ Login
$cus_id = $_SESSION['cus_id'] ?? $_SESSION['user_id'] ?? null;
if (!$cus_id) {
    header("Location: login.php");
    exit;
}

require_once 'db-connect.php';

date_default_timezone_set('Asia/Bangkok');

// กำหนดหมายเลข PromptPay
$promptpay = "0966343365";

// ดึงข้อความแจ้งเตือนจาก Session (ถ้ามี)
$message = $_SESSION['flash_message'] ?? '';
$message_type = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

// 🟢 ฟังก์ชันรันรหัสคูปองตาม Prefix (TM สำหรับนวดไทย / FM สำหรับนวดหน้าเด้ง)
function generateNextCouponCode($pdo, $prefix = 'TM') {
    // ดึงรหัสคูปองล่าสุดที่ขึ้นต้นด้วย Prefix นั้นๆ
    $sql = "SELECT coupon_code 
            FROM cus_coupon 
            WHERE coupon_code REGEXP :pattern 
            ORDER BY LENGTH(coupon_code) DESC, coupon_code DESC 
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':pattern' => '^' . $prefix . '[0-9]+$']);
    $last_code = $stmt->fetchColumn();

    if ($last_code) {
        // ตัดตัวอักษร Prefix ออกเพื่อเอาเฉพาะตัวเลข เช่น TM00005 -> 5
        $num = intval(substr($last_code, strlen($prefix)));
        $next_num = $num + 1;
    } else {
        $next_num = 1;
    }

    return $prefix . str_pad($next_num, 5, '0', STR_PAD_LEFT);
}

// ----------------------------------------------------
// ประมวลผลการสั่งซื้อเล่มคูปองจาก Modal Pop-up
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'buy_coupon_book') {
    $coupon_id = intval($_POST['coupon_id'] ?? 0);
    $payment_method = $_POST['payment_method'] ?? 'transfer';

    if (!in_array($payment_method, ['cash', 'transfer'])) {
        $payment_method = 'transfer';
    }

    // จัดการอัปโหลดไฟล์สลิปโอนเงิน (ถ้ามี)
    $slip_filename = null;
    if ($payment_method === 'transfer' && isset($_FILES['slip_image']) && $_FILES['slip_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['slip_image']['tmp_name'];
        $file_name = $_FILES['slip_image']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($file_ext, $allowed_exts)) {
            $upload_dir = 'uploads/slips/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $slip_filename = 'slip_coupon_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
            move_uploaded_file($file_tmp, $upload_dir . $slip_filename);
        }
    }

    // 1. ดึงข้อมูลเล่มคูปอง
    $sql_c = "SELECT * FROM coupon WHERE coupon_id = :coupon_id";
    $stmt_c = $pdo->prepare($sql_c);
    $stmt_c->execute([':coupon_id' => $coupon_id]);
    $target_coupon = $stmt_c->fetch(PDO::FETCH_ASSOC);

    if ($target_coupon) {
        try {
            $pdo->beginTransaction();

            $total_price = $target_coupon['price'];
            $total_leaves = intval($target_coupon['total_leaves']);

            // 🟢 ดึง service_id และชื่อบริการที่ผูกกับ coupon_id นี้
            $sql_s = "SELECT id, name FROM allservices WHERE coupon_id = :coupon_id ORDER BY duration ASC, id ASC LIMIT 1";
            $stmt_s = $pdo->prepare($sql_s);
            $stmt_s->execute([':coupon_id' => $coupon_id]);
            $service_data = $stmt_s->fetch(PDO::FETCH_ASSOC);
            $service_id = $service_data['id'] ?? null;
            $service_name = $service_data['name'] ?? '';

            // 🟢 กำหนด Prefix ตามชื่อแพ็กเกจ/บริการ (ถ้าเป็นหน้าเด้งใช้ FM / ที่เหลือใช้ TM)
            $coupon_title = $target_coupon['coupon_name'] . ' ' . $service_name;
            $prefix = (mb_strpos($coupon_title, 'หน้า') !== false) ? 'FM' : 'TM';

            // 2. บันทึกลงตาราง coupon_book
            $sql_book = "INSERT INTO coupon_book (cus_id, coupon_id, total_price, payment_method, slip_image, sold_date) 
                         VALUES (:cus_id, :coupon_id, :total_price, :payment_method, :slip_image, NOW())";
            $stmt_book = $pdo->prepare($sql_book);$stmt_book->execute([
                ':cus_id' => $cus_id,
                ':coupon_id' => $coupon_id,
                ':total_price' => $total_price,
                ':payment_method' => $payment_method,
                ':slip_image' => $slip_filename
            ]);

            $coupon_book_id =$pdo->lastInsertId();

            // 3. สร้างใบคูปองลงตาราง cus_coupon โดยใช้ Prefix ตามประเภท
            $sql_cus_coupon = "INSERT INTO cus_coupon (coupon_book_id, cus_id, coupon_id, service_id, coupon_code, used) 
                               VALUES (:coupon_book_id, :cus_id, :coupon_id, :service_id, :coupon_code, 0)";
            $stmt_cc = $pdo->prepare($sql_cus_coupon);

            for ($i = 0; $i < $total_leaves; $i++) {
                // 🟢 รันโค้ดรหัสคูปองถัดไปของ Prefix นั้นๆ
                $coupon_code = generateNextCouponCode($pdo, $prefix);$stmt_cc->execute([
                    ':coupon_book_id' => $coupon_book_id,
                    ':cus_id' => $cus_id,
                    ':coupon_id' => $coupon_id,
                    ':service_id' => $service_id ?: null,
                    ':coupon_code' => $coupon_code
                ]);
            }

            $pdo->commit();

            $_SESSION['flash_message'] = "สั่งซื้อเล่มคูปองสำเร็จ! คุณได้รับคูปองเพิ่ม {$total_leaves} ใบ";
            $_SESSION['flash_type'] = "success";

        } catch (Exception $e) {
            $pdo->rollBack();$_SESSION['flash_message'] = 'เกิดข้อผิดพลาดในการทำรายการ: ' . $e->getMessage();$_SESSION['flash_type'] = 'error';
        }
    } else {
        $_SESSION['flash_message'] = 'ไม่พบข้อมูลเล่มคูปองที่เลือก';
        $_SESSION['flash_type'] = 'error';
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// ----------------------------------------------------
// 1. ดึงคูปองของลูกค้าจากตาราง cus_coupon
// ----------------------------------------------------
$sql_user_coupons = "
    SELECT 
        cc.id AS cus_coupon_id,
        cc.coupon_id,
        cc.coupon_book_id,
        cc.coupon_code,
        cc.used,
        c.coupon_name,
        c.price,
        s.name AS service_name,
        s.duration
    FROM cus_coupon cc
    INNER JOIN coupon c ON cc.coupon_id = c.coupon_id
    LEFT JOIN allservices s ON cc.service_id = s.id
    WHERE cc.cus_id = :cus_id
    ORDER BY cc.coupon_book_id DESC, cc.id DESC
";
$stmt_u = $pdo->prepare($sql_user_coupons);
$stmt_u->execute([':cus_id' =>$cus_id]);
$coupons =$stmt_u->fetchAll(PDO::FETCH_ASSOC);

$coupon_books = [];
foreach ($coupons as$coupon) {
    $book_key = !empty($coupon['coupon_book_id'])
        ? 'book-' . $coupon['coupon_book_id']
        : 'legacy-' . $coupon['coupon_id'];

    if (!isset($coupon_books[$book_key])) {
        $coupon_books[$book_key] = [
            'coupon_book_id' => $coupon['coupon_book_id'],
            'coupon_name' => $coupon['coupon_name'],
            'coupons' => [],
            'used_count' => 0
        ];
    }

    $coupon_books[$book_key]['coupons'][] =$coupon;
    if ((int)$coupon['used'] === 1) {
        $coupon_books[$book_key]['used_count']++;
    }
}

// ----------------------------------------------------
// 2. ดึงรายการเล่มคูปองที่มีขายจากตาราง coupon (พร้อม JOIN ชื่อบริการ)
// ----------------------------------------------------
$sql_available_coupons = "
    SELECT c.*, s.name AS service_name, s.duration AS service_duration
    FROM coupon c 
    LEFT JOIN allservices s ON s.coupon_id = c.coupon_id
    GROUP BY c.coupon_id 
    ORDER BY c.price ASC
";
$stmt_a =$pdo->prepare($sql_available_coupons);$stmt_a->execute();
$available_coupons =$stmt_a->fetchAll(PDO::FETCH_ASSOC);

function getCouponBadge($used) {
    if ($used == 1) {
        return '<span class="bg-gray-100 text-gray-600 text-xs font-semibold px-2.5 py-1 rounded-full border border-gray-300">✅ ใช้แล้ว</span>';
    }
    return '<span class="bg-emerald-100 text-emerald-800 text-xs font-semibold px-2.5 py-1 rounded-full border border-emerald-300">🎟️ ใช้งานได้</span>';
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>คูปองของฉัน - Baanbodinspa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; }
    </style>
</head>
<body class="bg-[#FAF6F0] text-gray-800 min-h-screen flex flex-col">

    <?php include 'includes/nav3.php'; ?>
    
    <main class="flex-grow container mx-auto px-4 py-8 max-w-4xl">
        
        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 bg-white p-6 rounded-3xl shadow-sm border border-[#E8E2D5]">
            <div>
                <h1 class="text-2xl font-bold text-[#382214]">คูปองส่วนลดของฉัน</h1>
                <p class="text-base text-gray-500 mt-1">คูปองของ <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'ลูกค้า'); ?> ทั้งหมด</p>
            </div>
            <div class="mt-4 sm:mt-0 flex gap-2">
                <button type="button" onclick="openBuyModal()" class="bg-[#382214] text-white px-5 py-2.5 rounded-xl text-sm font-medium hover:bg-[#5A3A29] transition-colors shadow-md flex items-center gap-1.5">
                    🛒 ซื้อคูปองเพิ่ม
                </button>
                <a href="booking.php" class="bg-[#FAF6F0] text-[#382214] border border-[#E8E2D5] px-4 py-2.5 rounded-xl text-sm font-medium hover:bg-[#E8E2D5] transition-colors">
                    + ใช้คูปองจองคิว
                </a>
            </div>
        </div>

        <!-- แจ้งเตือนสถานะการทำรายการ -->
        <?php if (!empty($message)): ?>
            <div role="<?= $message_type === 'error' ? 'alert' : 'status' ?>" aria-live="polite" class="mb-6 p-4 rounded-2xl text-sm font-medium flex items-start justify-between gap-4 <?= $message_type === 'error' ? 'bg-rose-100 text-rose-800 border border-rose-300' : 'bg-emerald-100 text-emerald-800 border border-emerald-300' ?>">
                <span><?= htmlspecialchars($message) ?></span>
                <button type="button" onclick="this.parentElement.remove()" aria-label="ปิดการแจ้งเตือน" class="shrink-0 text-lg leading-none opacity-70 hover:opacity-100">
                    &times;
                </button>
            </div>
        <?php endif; ?>

        <?php if (empty($coupons)): ?>
            <!-- กรณีไม่มีคูปอง -->
            <div class="bg-white rounded-3xl p-12 text-center shadow-sm border border-[#E8E2D5] space-y-4">
                <div class="text-5xl">🎟️</div>
                <h3 class="text-lg font-bold text-[#382214]">ยังไม่มีคูปองใช่ไหม ซื้อเลย!</h3>
                <p class="text-sm text-gray-500">เลือกซื้อแพ็กเกจเล่มคูปองสุดคุ้ม</p>
                <button type="button" onclick="openBuyModal()" class="inline-block bg-[#382214] text-white px-6 py-2.5 rounded-xl text-sm font-medium hover:bg-[#5A3A29] transition-colors shadow-md">
                    🛒 เลือกซื้อคูปอง
                </button>
            </div>
        <?php else: ?>

            <!-- รายการคูปองแยกตามเล่ม -->
            <div class="space-y-6">
                <?php foreach ($coupon_books as$book):
                    $total_coupons = count($book['coupons']);
                ?>
                    <section class="bg-white rounded-3xl shadow-sm border border-[#E8E2D5] p-5 sm:p-6">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
                            <div>
                                <h2 class="text-lg font-bold text-[#382214]">
                                    <?= htmlspecialchars($book['coupon_name']) ?>
                                    <?php if (!empty($book['coupon_book_id'])): ?>
                                        <span class="text-sm font-medium text-gray-500">- เล่มที่ <?= htmlspecialchars((string)$book['coupon_book_id']) ?></span>
                                    <?php else: ?>
                                        <span class="text-sm font-medium text-gray-500">- ไม่ระบุเล่ม</span>
                                    <?php endif; ?>
                                </h2>
                            </div>
                            <div class="inline-flex items-center gap-1.5 self-start sm:self-auto bg-[#FAF6F0] border border-[#E8E2D5] rounded-xl px-3 py-2 text-sm">
                                <span class="font-bold text-[#382214]">ใช้แล้ว <?= number_format($book['used_count']) ?>/<?= number_format($total_coupons) ?> ใบ</span>
                                <span class="text-gray-500">(ทั้งหมด)</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php foreach ($book['coupons'] as$coupon):
                                $is_used = ((int)$coupon['used'] === 1);
                            ?>
                                <div class="bg-[#FDFBF7] rounded-2xl shadow-sm border border-[#E8E2D5] overflow-hidden flex flex-col justify-between transition-all <?php echo $is_used ? 'opacity-60 grayscale-[30%]' : 'hover:shadow-md'; ?>">
                                    <div class="p-4 sm:p-5 space-y-3">
                                        <div class="flex justify-between items-start gap-2">
                                            <span class="text-sm font-bold uppercase tracking-wider text-[#5A3A29] bg-white px-3 py-1 rounded-lg border border-[#E8E2D5]">
                                                โค้ด: <?= htmlspecialchars($coupon['coupon_code']) ?>
                                            </span>
                                            <?= getCouponBadge($coupon['used']) ?>
                                        </div>

                                        <div>
                                            <h3 class="text-lg font-bold text-[#382214]">
                                                <?= htmlspecialchars($coupon['coupon_name']) ?>
                                            </h3>
                                            <p class="text-sm text-gray-500 mt-1 leading-relaxed">
                                                ใช้สิทธิ์สำหรับ: <strong class="text-[#382214]"><?= htmlspecialchars($coupon['coupon_name']) ?></strong> (ครอบคลุมบริการ 60-120 นาที)
                                            </p>
                                        </div>
                                    </div>

                                    <div class="border-t border-dashed border-[#E8E2D5] p-4 flex justify-between items-center text-xs text-gray-500">
                                        <span class="text-gray-400 font-medium">Baanbodinspa Coupon</span>
                                        <?php if (!$is_used): ?>
                                            <a href="booking.php?coupon=<?= urlencode($coupon['coupon_code']) ?>" class="bg-[#382214] text-white px-4 py-1.5 rounded-xl font-medium hover:bg-[#5A3A29] transition-colors shadow-sm">
                                                ใช้คูปองจองคิว
                                            </a>
                                        <?php else: ?>
                                            <span class="text-gray-400 font-medium">ใช้สิทธิ์แล้ว</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>

    </main>

    <!-- ========================================================= -->
    <!-- MODAL POP-UP: เลือกซื้อเล่มคูปอง -->
    <!-- ========================================================= -->
    <div id="buyModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center p-4 z-50 overflow-y-auto">
        <div class="bg-white rounded-3xl max-w-2xl w-full p-6 sm:p-8 shadow-xl border border-[#E8E2D5] space-y-6 relative max-h-[90vh] flex flex-col my-auto">
            
            <!-- Header Modal -->
            <div class="flex justify-between items-center border-b border-[#E8E2D5] pb-4">
                <div>
                    <h3 class="text-xl font-bold text-[#382214]">🛒 เลือกซื้อเล่มคูปอง</h3>
                    <p class="text-sm text-gray-500 mt-0.5">เลือกแพ็กเกจคูปองที่ต้องการเพื่อรับส่วนลดสุดคุ้ม</p>
                </div>
                <button type="button" onclick="closeBuyModal()" class="text-gray-400 hover:text-gray-600 text-2xl font-bold p-1">
                    &times;
                </button>
            </div>

            <!-- List เล่มคูปองที่มีให้เลือก -->
            <div class="overflow-y-auto flex-grow space-y-4 pr-1">
                <?php if (empty($available_coupons)): ?>
                    <p class="text-center text-gray-500 text-sm py-8">ขณะนี้ยังไม่มีรายการเล่มคูปองเปิดขาย</p>
                <?php else: ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <?php foreach ($available_coupons as$acp): ?>
                            <div class="border border-[#E8E2D5] rounded-2xl p-4 bg-[#FAF6F0]/50 hover:bg-[#FAF6F0] hover:border-[#382214] transition-all flex flex-col justify-between gap-3">
                                <div class="space-y-1.5">
                                    <div class="flex justify-between items-center">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#5A3A29] bg-white px-2 py-0.5 rounded border border-[#E8E2D5]">
                                            แพ็กเกจ
                                        </span>
                                        <span class="text-xs font-semibold text-amber-800 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200">
                                            🎫 ได้ <?= $acp['total_leaves'] ?> ใบ
                                        </span>
                                    </div>
                                    <h4 class="font-bold text-[#382214] text-base mt-1">
                                        <?= htmlspecialchars($acp['coupon_name']) ?>
                                    </h4>
                                    <?php if (!empty($acp['service_name'])): ?>
                                        <p class="text-xs text-gray-500">
                                            ใช้ได้กับ: <?= htmlspecialchars($acp['service_name']) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <div class="pt-2 border-t border-[#E8E2D5]/60 flex justify-between items-center">
                                    <div>
                                        <div class="text-[10px] text-gray-400">ราคารวม</div>
                                        <div class="text-base font-extrabold text-[#047857]">
                                            ฿<?= number_format($acp['price'], 2) ?>
                                        </div>
                                    </div>
                                    <button type="button" onclick="selectCouponForCheckout(<?= $acp['coupon_id'] ?>, '<?= htmlspecialchars($acp['coupon_name'], ENT_QUOTES) ?>', <?= $acp['price'] ?>, <?=$acp['total_leaves'] ?>)" 
                                            class="bg-[#382214] text-white text-xs px-3.5 py-2 rounded-xl font-medium hover:bg-[#5A3A29] transition-colors shadow-sm">
                                        เลือกซื้อ
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ฟอร์มยืนยันและชำระเงิน -->
            <form id="checkoutForm" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" enctype="multipart/form-data" class="hidden border-t border-[#E8E2D5] pt-4 space-y-4">
                <input type="hidden" name="action" value="buy_coupon_book">
                <input type="hidden" name="coupon_id" id="modalCouponId" value="">

                <div class="bg-[#FAF6F0] p-4 rounded-2xl border border-[#E8E2D5] space-y-1.5 text-xs sm:text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">แพ็กเกจที่เลือก:</span>
                        <span id="selectedTitle" class="font-bold text-[#382214]"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">จำนวนใบคูปองที่จะได้รับ:</span>
                        <span id="selectedLeaves" class="font-bold text-[#382214]"></span>
                    </div>
                    <div class="flex justify-between border-t border-[#E8E2D5] pt-2">
                        <span class="text-gray-500">ยอดชำระสุทธิ:</span>
                        <span id="selectedPrice" class="font-bold text-[#047857] text-base"></span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-2">เลือกวิธีชำระเงิน</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="border border-[#E8E2D5] p-3 rounded-xl flex items-center gap-2 cursor-pointer hover:bg-[#FAF6F0]">
                            <input type="radio" name="payment_method" value="transfer" checked onchange="togglePaymentSection('transfer')" class="accent-[#382214]">
                            <span class="text-xs font-medium">🏦 โอนเงินผ่านธนาคาร</span>
                        </label>
                        <label class="border border-[#E8E2D5] p-3 rounded-xl flex items-center gap-2 cursor-pointer hover:bg-[#FAF6F0]">
                            <input type="radio" name="payment_method" value="cash" onchange="togglePaymentSection('cash')" class="accent-[#382214]">
                            <span class="text-xs font-medium">💵 เงินสดหน้าร้าน</span>
                        </label>
                    </div>
                </div>

                <div id="transferSection" class="bg-white p-4 rounded-2xl border border-[#E8E2D5] space-y-3">
                    <div class="text-center">
                        <p class="text-xs font-bold text-[#382214]">สแกน QR Code เพื่อโอนเงินชำระค่าคูปอง</p>
                        <p class="text-[11px] text-gray-500">พร้อมเพย์: <?= htmlspecialchars($promptpay) ?></p>
                        
                        <div class="mt-3 flex justify-center">
                            <img id="qrCodeImg" src="" alt="PromptPay QR Code" class="w-44 h-44 object-contain border border-gray-200 p-2 rounded-2xl shadow-sm bg-white">
                        </div>
                    </div>

                    <div class="pt-2 border-t border-gray-100">
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">อัปโหลดสลิปการโอนเงิน</label>
                        <input type="file" name="slip_image" accept="image/*" class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-[#382214] file:text-white hover:file:bg-[#5A3A29]">
                    </div>
                </div>

                <div class="flex gap-3 pt-1">
                    <button type="button" onclick="cancelSelection()" class="flex-1 bg-gray-100 text-gray-700 py-2.5 rounded-xl text-sm font-medium hover:bg-gray-200 transition-colors">
                        เปลี่ยนรายการ
                    </button>
                    <button type="submit" class="flex-1 bg-[#382214] text-white py-2.5 rounded-xl text-sm font-medium hover:bg-[#5A3A29] transition-colors shadow-md">
                        ยืนยันการซื้อ
                    </button>
                </div>
            </form>

        </div>
    </div>

    <!-- Script ควบคุม Modal -->
    <script>
        const promptPayNo = "<?= $promptpay ?>";

        function openBuyModal() {
            const modal = document.getElementById('buyModal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        }

        function closeBuyModal() {
            const modal = document.getElementById('buyModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
            cancelSelection();
        }

        function selectCouponForCheckout(id, title, price, leaves) {
            document.getElementById('modalCouponId').value = id;
            document.getElementById('selectedTitle').innerText = title;
            document.getElementById('selectedLeaves').innerText = leaves + ' ใบ';
            document.getElementById('selectedPrice').innerText = '฿' + Number(price).toLocaleString('th-TH', {minimumFractionDigits: 2});
            
            const qrUrl = `https://promptpay.io/${promptPayNo}/${price}.png`;
            document.getElementById('qrCodeImg').src = qrUrl;

            document.getElementById('checkoutForm').classList.remove('hidden');
        }

        function togglePaymentSection(method) {
            const transferSec = document.getElementById('transferSection');
            if (method === 'transfer') {
                transferSec.classList.remove('hidden');
            } else {
                transferSec.classList.add('hidden');
            }
        }

        function cancelSelection() {
            document.getElementById('modalCouponId').value = '';
            document.getElementById('checkoutForm').classList.add('hidden');
        }
    </script>

</body>
</html>