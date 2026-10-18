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

// 1. ดึงรายการ booking_group_id แบบไม่ให้ซ้ำกัน โดยดึงวันที่และเวลาของกลุ่มนั้นๆ มาด้วย
$sql_groups = "SELECT booking_group_id, MIN(booking_date) AS booking_date, MIN(booking_time) AS booking_time, MIN(created_at) AS created_at 
               FROM bookings 
               WHERE cus_id = :cus_id 
               GROUP BY booking_group_id 
               ORDER BY MAX(created_at) DESC";
$stmt_g = $pdo->prepare($sql_groups);
$stmt_g->execute([':cus_id' => $cus_id]);
$booking_groups = $stmt_g->fetchAll(PDO::FETCH_ASSOC);

// 2. ฟังก์ชันช่วยแสดงผล badge สถานะ
function getStatusBadge($status) {
    switch ($status) {
        case 'pending':
            return '<span class="bg-amber-100 text-amber-800 text-xs font-semibold px-2.5 py-1 rounded-full border border-amber-300">⏳ รอยืนยัน / รอจัดสรร</span>';
        case 'confirmed':
            return '<span class="bg-emerald-100 text-emerald-800 text-xs font-semibold px-2.5 py-1 rounded-full border border-emerald-300">✅ ยืนยันแล้ว</span>';
        case 'completed':
            return '<span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-1 rounded-full border border-blue-300">🎉 นวดเสร็จสิ้น</span>';
        case 'cancelled':
            return '<span class="bg-rose-100 text-rose-800 text-xs font-semibold px-2.5 py-1 rounded-full border border-rose-300">❌ ยกเลิกแล้ว</span>';
        default:
            return '<span class="bg-gray-100 text-gray-800 text-xs font-semibold px-2.5 py-1 rounded-full">' . htmlspecialchars($status) . '</span>';
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการจอง - Baanbodinspa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; }
    </style>
</head>

<body class="bg-[#FAF6F0] text-gray-800 min-h-screen flex flex-col">

    <?php include 'includes/nav3.php'; ?>

    <main class="flex-grow container mx-auto px-4 py-8 max-w-4xl">
        
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 bg-white p-6 rounded-3xl shadow-sm border border-[#E8E2D5]">
            <div>
                <h1 class="text-2xl font-bold text-[#382214]">ประวัติการจองของคุณ</h1>
                <p class="text-sm text-gray-500 mt-1">ยินดีต้อนรับคุณ <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'ลูกค้า'); ?></p>
            </div>
            <a href="booking.php" class="mt-4 sm:mt-0 bg-[#382214] text-white px-5 py-2.5 rounded-xl text-sm font-medium hover:bg-[#5A3A29] transition-colors shadow-md">
                + จองคิวใหม่
            </a>
        </div>

        <?php if (empty($booking_groups)): ?>
            <!-- กรณีไม่มีประวัติการจอง -->
            <div class="bg-white rounded-3xl p-12 text-center shadow-sm border border-[#E8E2D5] space-y-4">
                <div class="text-5xl"></div>
                <h3 class="text-lg font-bold text-[#382214]">ยังไม่มีประวัติการจองคิว</h3>
                <p class="text-sm text-gray-500">คุณยังไม่ได้ทำการจองคิวนวดกับ Baan Bodin Spa</p>
                <a href="booking.php" class="inline-block bg-[#382214] text-white px-6 py-2.5 rounded-xl text-sm font-medium hover:bg-[#5A3A29] transition-colors">
                    ทำการจองตอนนี้
                </a>
            </div>
        <?php else: ?>

            <!-- แสดงรายการการจองแยกตาม Group ID -->
            <div class="space-y-6">
                <?php foreach ($booking_groups as $group): 
                    $group_id = $group['booking_group_id'];

                // ดึงรายละเอียดการจองย่อยในกลุ่มนี้
                    $sql_details = "
                        SELECT 
                            b.*, 
                            s.name AS service_name, 
                            s.duration AS service_duration, 
                            s.price AS service_price,
                            st.nickname AS staff_nickname,
                            st.name AS staff_name
                        FROM bookings b
                        LEFT JOIN allservices s ON b.service_id = s.id
                        LEFT JOIN staffs st ON b.staff_id = st.staff_id
                        WHERE b.booking_group_id = :group_id
                        ORDER BY b.id ASC
                    ";
                    $stmt_d = $pdo->prepare($sql_details);
                    $stmt_d->execute([':group_id' => $group_id]);
                    $items = $stmt_d->fetchAll(PDO::FETCH_ASSOC);

                    // คำนวณราคารวม และตรวจสอบว่าทุกคนในกลุ่มนวดเสร็จ (completed) ครบทั้งหมดหรือยัง
                    $group_total_price = 0;
                    $all_completed = true;
                    $has_cancelled = false;
                    // ตัวแปรนับจำนวน
                    $completed_count = 0;
                    $total_count = count($items);
                    // ตัวแปรสำหรับนับจำนวนคนยกเลิก และคนทั้งหมดที่ยังไม่ยกเลิก
                    $cancelled_count = 0;
                    $active_total_count = 0; // จำนวนคนที่ไม่ถูกยกเลิก (เอาไว้เทียบสัดส่วนจ่ายเงิน)
                    $all_cancelled = true;   // เช็กว่ายกเลิกหมดทุกคนเลยไหม
                    foreach ($items as $it) {
                        // ถ้ารายการนี้ไม่ถูกยกเลิก ถึงจะนำมารวมราคา
                        if ($it['status'] !== 'cancelled') {
                            $group_total_price += ($it['service_price'] ?? 0);
                            $active_total_count++;
                            $all_cancelled = false;
                        } else {
                            $cancelled_count++;
                        }
                        
                        // เช็กสถานะ completed เฉพาะคนที่ไม่ได้ยกเลิก
                        if ($it['status'] === 'completed') {
                            $completed_count++;
                        } elseif ($it['status'] !== 'cancelled') {
                            $all_completed = false;
                        }
                    }

                    // 🟢 กำหนดสถานะรวมของกลุ่ม (Group Status) 
                    if ($all_cancelled) {
                        $display_status = 'cancelled'; // ถ้าทุกคนในกลุ่มยกเลิกหมด
                    } elseif ($all_completed && $active_total_count > 0) {
                        $display_status = 'completed'; // ถ้าคนที่เหลืออยู่ นวดเสร็จและจ่ายเงินครบทุกคนแล้ว
                    } else {
                        $display_status = 'confirmed'; // สถานะปกติ (มีบางคนกำลังนวด หรือรอดำเนินการ)
                    }

                    // จัดกลุ่มบริการตามลูกค้าแต่ละคน
                    $people = [];
                    foreach ($items as $it) {
                        $person_num = $it['customer_count'] ?? 1; // หรือใช้ลำดับ index
                        $people[$person_num][] = $it;
                    }
                ?>
                    <div class="bg-white rounded-3xl shadow-sm border border-[#E8E2D5] overflow-hidden">
                        
                        <!-- Header ของการจองกลุ่มนี้ -->
                        <div class="bg-[#FDFBF7] p-4 sm:p-5 border-b border-[#E8E2D5] flex flex-wrap justify-between items-center gap-2">
                            <div>
                                <div class="text-xs text-gray-500">หมายเลขอ้างอิง: <span class="font-mono font-bold text-[#382214]"><?= htmlspecialchars($group_id) ?></span></div>
                                <div class="text-base font-bold text-[#382214] mt-0.5">
                                    📅 วันที่ <?= date('d/m/Y', strtotime($items[0]['booking_date'])) ?> เวลา <?= date('H:i', strtotime($items[0]['booking_time'])) ?> น.
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-2">
                                <!-- 🟢 แสดงป้ายสัดส่วน (เทียบกับจำนวนคนที่ไม่ถูกยกเลิก) -->
                                <?php if ($display_status !== 'completed' && $display_status !== 'cancelled' && $completed_count > 0): ?>
                                    <span class="text-xs font-semibold text-amber-800 bg-amber-50 px-2.5 py-1 rounded-full border border-amber-300">
                                        ชำระแล้ว <?= $completed_count ?>/<?= $active_total_count ?> คน
                                    </span>
                                <?php endif; ?>

                                <!-- แสดง Badge สถานะหลักของกลุ่ม -->
                                <?= getStatusBadge($display_status) ?>
                            </div>
                        </div>

                        <!-- รายละเอียดบริการของลูกค้าแต่ละคน -->
                        <div class="p-4 sm:p-5 space-y-4">
                            <?php 
                            $person_counter = 1;
                            // วนลูปแสดงตามพนักงาน/รายการบริการ
                            foreach ($items as $index => $item): 
                            ?>
                                <div class="p-3 bg-[#FAF6F0]/60 rounded-2xl border border-gray-100 flex flex-col sm:flex-row justify-between sm:items-center gap-2 text-sm">
                                <div class="space-y-1">
                                    <div class="font-bold text-[#382214] flex flex-wrap items-center gap-2">
                                        <span><?= htmlspecialchars($item['service_name'] ?? 'ไม่ระบุบริการ') ?></span>
                                        
                                        <!-- 🟢 เพิ่มตรงนี้: แสดงป้ายสถานะแยกตามแต่ละรายการย่อย -->
                                        <?= getStatusBadge($item['status']) ?>
                                    </div>
                                    
                                    <div class="text-xs text-gray-600">
                                         เวลาบริการ: <?= $item['service_duration'] ?? 0 ?> นาที
                                    </div>
                                    <div class="text-xs text-gray-600">
                                        พนักงาน: 
                                        <?php if (!empty($item['staff_nickname'] || !empty($item['staff_name']))): ?>
                                            <span class="font-semibold text-[#382214]">หมอ <?= htmlspecialchars($item['staff_nickname'] ?? $item['staff_name']) ?></span>
                                        <?php else: ?>
                                            <span class="text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md font-medium">⏳ ไม่ระบุ (ทางร้านจะจัดสรรให้)</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                    
                                    <div class="text-right sm:text-right font-bold text-[#5A3A29]">
                                        <?= number_format($item['service_price'] ?? 0) ?> บาท
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Footer สรุปราคารวมของการจองนี้ -->
                        <div class="p-4 sm:p-5 bg-gray-50/50 border-t border-gray-100 flex justify-between items-center text-sm">
                            <span class="text-xs text-gray-500">
                                ทำรายการเมื่อ: <?= date('d/m/Y H:i', strtotime($group['created_at'])) ?> น.
                            </span>
                            <div class="text-base font-bold text-[#382214]">
                                ราคารวมสุทธิ: <span class="text-lg text-[#047857]"><?= number_format($group_total_price) ?></span> บาท
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>

    </main>

</body>

</html>