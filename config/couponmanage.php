<?php
// ----------------------------------------------------
// 1. ดึงรายการบริการทั้งหมดสำหรับ Dropdown เลือกบริการ
// ----------------------------------------------------
$sql_all_services = "SELECT id, name, duration, coupon_id FROM allservices WHERE status = 'active' ORDER BY name ASC";
$stmt_as = $pdo->prepare($sql_all_services);
$stmt_as->execute();
$service_options = $stmt_as->fetchAll(PDO::FETCH_ASSOC);

// ----------------------------------------------------
// 2. ประมวลผล Backend CRUD สำหรับจัดการแพ็กเกจคูปอง
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['coupon_action'])) {
    $action = $_POST['coupon_action'];

    if ($action === 'add') {
        $coupon_name  = trim($_POST['coupon_name']);
        $service_id   = !empty($_POST['service_id']) ? intval($_POST['service_id']) : null;
        $price        = floatval($_POST['price']);
        $total_leaves = intval($_POST['total_leaves']);

        // 2.1 บันทึกแพ็กเกจใหม่ลงตาราง coupon
        $sql_add = "INSERT INTO coupon (coupon_name, price, total_leaves) VALUES (:coupon_name, :price, :total_leaves)";
        $stmt_add = $pdo->prepare($sql_add);
        $stmt_add->execute([
            ':coupon_name'  => $coupon_name,
            ':price'        => $price,
            ':total_leaves' => $total_leaves
        ]);
        $new_coupon_id = $pdo->lastInsertId();

        // 2.2 ถ้ามีการเลือกบริการ ให้ผูก coupon_id เข้ากับบริการนั้นใน allservices
        if ($service_id && $new_coupon_id) {
            // เคลียร์ coupon_id เดิมที่อาจผูกคูปองนี้อยู่ก่อนหน้า (ถ้ามี)
            $pdo->prepare("UPDATE allservices SET coupon_id = NULL WHERE coupon_id = :cid")->execute([':cid' => $new_coupon_id]);
            // ผูกคูปองใหม่เข้ากับบริการที่เลือก
            $stmt_up_srv = $pdo->prepare("UPDATE allservices SET coupon_id = :cid WHERE id = :sid");
            $stmt_up_srv->execute([':cid' => $new_coupon_id, ':sid' => $service_id]);
        }

        $_SESSION['flash_message'] = "เพิ่มแพ็กเกจคูปองเรียบร้อยแล้ว";
        $_SESSION['flash_type']    = "success";

    } elseif ($action === 'edit') {
        $coupon_id    = intval($_POST['coupon_id']);
        $coupon_name  = trim($_POST['coupon_name']);
        $service_id   = !empty($_POST['service_id']) ? intval($_POST['service_id']) : null;
        $price        = floatval($_POST['price']);
        $total_leaves = intval($_POST['total_leaves']);

        // 2.1 อัปเดตข้อมูลแพ็กเกจคูปอง
        $sql_edit = "UPDATE coupon SET coupon_name = :coupon_name, price = :price, total_leaves = :total_leaves WHERE coupon_id = :coupon_id";
        $stmt_edit = $pdo->prepare($sql_edit);
        $stmt_edit->execute([
            ':coupon_name'  => $coupon_name,
            ':price'        => $price,
            ':total_leaves' => $total_leaves,
            ':coupon_id'   => $coupon_id
        ]);

        // 2.2 อัปเดตการผูกบริการใน allservices
        // เคลียร์บริการเดิมที่ผูกคูปองนี้ไว้
        $pdo->prepare("UPDATE allservices SET coupon_id = NULL WHERE coupon_id = :cid")->execute([':cid' => $coupon_id]);
        
        // ผูกบริการใหม่ที่เลือก
        if ($service_id) {
            $stmt_up_srv = $pdo->prepare("UPDATE allservices SET coupon_id = :cid WHERE id = :sid");
            $stmt_up_srv->execute([':cid' => $coupon_id, ':sid' => $service_id]);
        }

        $_SESSION['flash_message'] = "แก้ไขข้อมูลแพ็กเกจเรียบร้อยแล้ว";
        $_SESSION['flash_type']    = "success";

    } elseif ($action === 'delete') {
        $coupon_id = intval($_POST['coupon_id']);

        // เคลียร์การผูกคูปองใน allservices
        $pdo->prepare("UPDATE allservices SET coupon_id = NULL WHERE coupon_id = :cid")->execute([':cid' => $coupon_id]);

        // ลบแพ็กเกจ
        $sql_del = "DELETE FROM coupon WHERE coupon_id = :coupon_id";
        $stmt_del = $pdo->prepare($sql_del);
        $stmt_del->execute([':coupon_id' => $coupon_id]);

        $_SESSION['flash_message'] = "ลบแพ็กเกจคูปองเรียบร้อยแล้ว";
        $_SESSION['flash_type']    = "success";
    }

    echo "<script>window.location.href='dashboard.php?tab=coupons';</script>";
    exit;
}

// ----------------------------------------------------
// 3. ดึงข้อมูลแพ็กเกจคูปอง พร้อม LEFT JOIN ตาราง allservices
// ----------------------------------------------------
$sql_coupons_admin = "
    SELECT c.*, 
           GROUP_CONCAT(CONCAT(s.name, ' (', s.duration, ' นาที)') SEPARATOR ', ') AS bound_services_text,
           MIN(s.id) AS service_id
    FROM coupon c 
    LEFT JOIN allservices s ON s.coupon_id = c.coupon_id 
    GROUP BY c.coupon_id
    ORDER BY c.coupon_id ASC
";
$stmt_ca = $pdo->prepare($sql_coupons_admin);
$stmt_ca->execute();
$admin_coupons = $stmt_ca->fetchAll(PDO::FETCH_ASSOC);

$sql_customer_coupon_books = "
    SELECT 
        cb.coupon_book_id AS book_id,
        cb.cus_id,
        cb.total_price,
        cb.payment_method,
        cb.slip_image,
        cb.sold_date,
        c.name AS customer_name,
        c.phone AS customer_phone,
        cp.coupon_name,
        cp.total_leaves,
        SUM(CASE WHEN cc.used = 1 THEN 1 ELSE 0 END) AS used_count,
        GROUP_CONCAT(cc.coupon_code ORDER BY cc.id ASC SEPARATOR ', ') AS coupon_codes
    FROM coupon_book cb
    LEFT JOIN customers c ON cb.cus_id = c.cus_id
    LEFT JOIN coupon cp ON cb.coupon_id = cp.coupon_id
    LEFT JOIN cus_coupon cc ON cb.coupon_book_id = cc.coupon_book_id
    GROUP BY cb.coupon_book_id
    ORDER BY cb.sold_date DESC, cb.coupon_book_id DESC
";
$stmt_cb = $pdo->prepare($sql_customer_coupon_books);
$stmt_cb->execute();
$customer_books = $stmt_cb->fetchAll(PDO::FETCH_ASSOC);
?>

<section id="section-coupon" class="space-y-6 hidden">

    <!-- หัวข้อแท็บ และ ปุ่มเพิ่มใหม่ -->
    <div class="flex justify-between items-center">
        <div>
            <h3 class="text-2xl font-bold text-[#382214]">จัดการแพ็กเกจคูปอง</h3>
            <p class="text-sm text-gray-500 mt-0.5">เพิ่ม แก้ไข หรือลบรายการแพ็กเกจคูปองสำหรับวางจำหน่าย</p>
        </div>
        <button onclick="openModal('addCouponModal')" class="bg-[#047857] hover:bg-[#065f46] text-white px-4 py-2.5 rounded-xl font-medium shadow transition-all flex items-center gap-2">
            + เพิ่มแพ็กเกจใหม่
        </button>
    </div>

    <!-- ตารางแสดงรายการแพ็กเกจคูปอง -->
    <div class="bg-white rounded-2xl shadow-sm border border-[#E8E2D5] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-base text-gray-600">
                <thead class="bg-[#FDFBF7] text-lg text-[#382214] font-semibold border-b border-[#E8E2D5]">
                    <tr>
                        <th class="p-4">ลำดับ</th>
                        <th class="p-4">ชื่อแพ็กเกจคูปอง</th>
                        <th class="p-4">บริการที่ผูกไว้</th>
                        <th class="p-4">จำนวนใบในเล่ม</th>
                        <th class="p-4">ราคาเหมาเล่ม</th>
                        <th class="p-4 text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($admin_coupons)): ?>
                        <tr>
                            <td colspan="6" class="p-6 text-center text-gray-400">ยังไม่มีรายการแพ็กเกจคูปอง</td>
                        </tr>
                    <?php else: ?>
                        <?php $i = 1; foreach ($admin_coupons as $ac): ?>
                            <tr class="hover:bg-gray-50 transition-colors text-base">
                                <td class="p-4 font-medium"><?= $i++ ?></td>
                                <td class="p-4 font-bold text-[#382214]"><?= htmlspecialchars($ac['coupon_name']) ?></td>
                                <td class="p-4">
                                    <?php if (!empty($ac['bound_services_text'])): ?>
                                        <span class="inline-flex items-center gap-1 font-semibold text-emerald-800 bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 rounded-lg">
                                            <?= htmlspecialchars($ac['bound_services_text']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-400 font-normal text-sm">- ยังไม่ได้ผูกบริการ -</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4">
                                    <span class="inline-flex items-center gap-1 font-semibold text-[#382214]">
                                        <?= number_format($ac['total_leaves']) ?> ใบ
                                    </span>
                                </td>
                                <td class="p-4 font-bold text-[#047857]">฿<?= number_format($ac['price'], 2) ?></td>
                                <td class="p-4 text-center space-x-1">
                                    <button type="button"
                                            data-id="<?= $ac['coupon_id'] ?>"
                                            data-name="<?= htmlspecialchars($ac['coupon_name'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-service-id="<?= $ac['service_id'] ?? '' ?>"
                                            data-price="<?= $ac['price'] ?>"
                                            data-leaves="<?= $ac['total_leaves'] ?>"
                                            onclick="openEditCouponModal(this)"
                                            class="px-3 py-1 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-lg font-medium transition-colors">
                                        แก้ไข
                                    </button>

                                    <button type="button"
                                            onclick="openDeleteCouponModal(<?= $ac['coupon_id'] ?>, '<?= htmlspecialchars($ac['coupon_name'], ENT_QUOTES, 'UTF-8') ?>')"
                                            class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded-lg text-lg font-medium transition-colors">
                                        ลบ
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="space-y-4 pt-4 border-t border-[#E8E2D5]">
        <div>
            <h3 class="text-2xl font-bold text-[#382214]">ประวัติการสั่งซื้อคูปองของลูกค้า</h3>
            <p class="text-sm text-gray-500 mt-0.5">ตรวจสอบรายการเล่มคูปองที่ลูกค้าทำการสั่งซื้อทั้งหมด</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-[#E8E2D5] overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-base text-gray-600">
                    <thead class="bg-[#FDFBF7] text-lg text-[#382214] font-semibold border-b border-[#E8E2D5]">
                        <tr>
                            <th class="p-4">เล่มที่</th>
                            <th class="p-4">วันที่ซื้อ</th>
                            <th class="p-4">ลูกค้า</th>
                            <th class="p-4">แพ็กเกจที่ซื้อ</th>
                            <th class="p-4">สถานะการใช้</th>
                            <th class="p-4">ยอดชำระ</th>
                            <th class="p-4">วิธีชำระ</th>
                            <th class="p-4 text-center">หลักฐานสลิป</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (empty($customer_books)): ?>
                            <tr>
                                <td colspan="8" class="p-6 text-center text-gray-400">ยังไม่มีประวัติการสั่งซื้อคูปองของลูกค้า</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($customer_books as $cb): 
                                $total_leaves = (int)($cb['total_leaves'] ?? 0);
                                $used_count   = (int)($cb['used_count'] ?? 0);
                                $pm           = strtolower(trim($cb['payment_method'] ?? 'transfer'));
                            ?>
                                <tr class="hover:bg-gray-50 transition-colors text-base">
                                    <td class="p-4 font-bold text-[#382214]">#<?= $cb['book_id'] ?></td>
                                    <td class="p-4">
                                        <div class="font-semibold text-gray-800"><?= date('d/m/Y', strtotime($cb['sold_date'])) ?></div>
                                        <div class="text-xs text-gray-400"><?= date('H:i', strtotime($cb['sold_date'])) ?> น.</div>
                                    </td>
                                    <td class="p-4">
                                        <div class="font-bold text-[#382214]"><?= htmlspecialchars($cb['customer_name'] ?? 'ลูกค้าทั่วไป') ?></div>
                                        <div class="text-xs text-gray-500"><?= htmlspecialchars($cb['customer_phone'] ?? '-') ?></div>
                                    </td>
                                    <td class="p-4">
                                        <div class="font-bold text-gray-800"><?= htmlspecialchars($cb['coupon_name'] ?? 'ไม่ระบุแพ็กเกจ') ?></div>
                                        <?php if (!empty($cb['coupon_codes'])): ?>
                                            <div class="text-xs text-amber-800 font-semibold mt-1 max-w-xs truncate" title="<?= htmlspecialchars($cb['coupon_codes']) ?>">
                                                รหัส: <?= htmlspecialchars($cb['coupon_codes']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4">
                                        <span class="inline-flex items-center gap-1 text-sm font-semibold px-2.5 py-1 rounded-full border <?= ($used_count >= $total_leaves && $total_leaves > 0) ? 'bg-gray-100 text-gray-600 border-gray-300' : 'bg-emerald-50 text-emerald-800 border-emerald-200' ?>">
                                            ใช้แล้ว <?= $used_count ?>/<?= $total_leaves ?> ใบ
                                        </span>
                                    </td>
                                    <td class="p-4 font-bold text-[#047857]">฿<?= number_format($cb['total_price'], 2) ?></td>
                                    <td class="p-4 font-semibold uppercase text-sm">
                                        <?php if ($pm === 'cash'): ?>
                                            <span class="text-amber-800 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">💵 เงินสด</span>
                                        <?php else: ?>
                                            <span class="text-blue-800 bg-blue-50 px-2 py-0.5 rounded border border-blue-200">🏦 โอนเงิน</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4 text-center">
                                        <?php if (!empty($cb['slip_image'])): ?>
                                            <button type="button" 
                                                    onclick="openSlipPreview('uploads/slips/<?= htmlspecialchars($cb['slip_image']) ?>')" 
                                                    class="px-2.5 py-1 bg-purple-100 hover:bg-purple-200 text-purple-700 rounded-lg text-xs font-bold transition-colors">
                                                ดูสลิป
                                            </button>
                                        <?php else: ?>
                                            <span class="text-xs text-gray-400">- ไม่มี -</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- MODAL POP-UP: เพิ่มแพ็กเกจคูปองใหม่ -->
    <!-- ========================================================= -->
    <div id="addCouponModal" class="modal-backdrop fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="modal-content bg-[#FDFBF7] rounded-2xl p-6 w-full max-w-md shadow-2xl border border-[#E8E2D5]">
            <div class="flex justify-between items-center pb-3 border-b border-[#E8E2D5] mb-4">
                <h3 class="text-xl font-bold text-[#382214]">เพิ่มแพ็กเกจคูปองใหม่</h3>
                <button onclick="closeModal('addCouponModal')" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
            </div>

            <form action="dashboard.php?tab=coupons" method="POST" class="space-y-4">
                <input type="hidden" name="coupon_action" value="add">

                <div>
                    <label class="block text-sm font-semibold text-[#382214] mb-1">ชื่อแพ็กเกจคูปอง <span class="text-red-500">*</span></label>
                    <input type="text" name="coupon_name" required placeholder="เช่น นวดแผนไทย 60 นาที (แพ็กเกจ 6 ใบ)"
                           class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#047857]">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-[#382214] mb-1">บริการที่ต้องการผูกกับคูปองนี้</label>
                    <select name="service_id" class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#047857]">
                        <option value="">-- ไม่เลือก (ยังไม่ผูกบริการ) --</option>
                        <?php foreach ($service_options as $srv): ?>
                            <option value="<?= $srv['id'] ?>">
                                <?= htmlspecialchars($srv['name']) ?> (<?= $srv['duration'] ?> นาที)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold text-[#382214] mb-1">จำนวนใบในเล่ม <span class="text-red-500">*</span></label>
                        <input type="number" name="total_leaves" min="1" required placeholder="6"
                               class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#047857]">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-[#382214] mb-1">ราคาเหมาเล่ม (บาท) <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" name="price" min="0" required placeholder="2470.00"
                               class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#047857]">
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button" onclick="closeModal('addCouponModal')"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl text-sm font-medium hover:bg-gray-300 transition-colors">
                        ยกเลิก
                    </button>
                    <button type="submit"
                            class="px-5 py-2 bg-[#047857] text-white rounded-xl text-sm font-medium hover:bg-[#244735] transition-colors shadow">
                        บันทึกข้อมูล
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- MODAL POP-UP: แก้ไขแพ็กเกจคูปอง -->
    <!-- ========================================================= -->
    <div id="editCouponModal" class="modal-backdrop fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="modal-content bg-[#FDFBF7] rounded-2xl p-6 w-full max-w-md shadow-2xl border border-[#E8E2D5]">
            <div class="flex justify-between items-center pb-3 border-b border-[#E8E2D5] mb-4">
                <h3 class="text-xl font-bold text-[#382214]">แก้ไขแพ็กเกจคูปอง</h3>
                <button onclick="closeModal('editCouponModal')" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
            </div>

            <form action="dashboard.php?tab=coupons" method="POST" class="space-y-4">
                <input type="hidden" name="coupon_action" value="edit">
                <input type="hidden" id="edit_coupon_id" name="coupon_id">

                <div>
                    <label class="block text-sm font-semibold text-[#382214] mb-1">ชื่อแพ็กเกจคูปอง <span class="text-red-500">*</span></label>
                    <input type="text" id="edit_coupon_name" name="coupon_name" required
                           class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#047857]">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-[#382214] mb-1">บริการที่ต้องการผูกกับคูปองนี้</label>
                    <select id="edit_service_id" name="service_id" class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#047857]">
                        <option value="">-- ไม่เลือก (ยังไม่ผูกบริการ) --</option>
                        <?php foreach ($service_options as $srv): ?>
                            <option value="<?= $srv['id'] ?>">
                                <?= htmlspecialchars($srv['name']) ?> (<?= $srv['duration'] ?> นาที)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold text-[#382214] mb-1">จำนวนใบในเล่ม <span class="text-red-500">*</span></label>
                        <input type="number" id="edit_total_leaves" name="total_leaves" min="1" required
                               class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#047857]">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-[#382214] mb-1">ราคาเหมาเล่ม (บาท) <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" id="edit_coupon_price" name="price" min="0" required
                               class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#047857]">
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button" onclick="closeModal('editCouponModal')"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl text-sm font-medium hover:bg-gray-300 transition-colors">
                        ยกเลิก
                    </button>
                    <button type="submit"
                            class="px-5 py-2 bg-[#047857] text-white rounded-xl text-sm font-medium hover:bg-[#244735] transition-colors shadow">
                        บันทึกการแก้ไข
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- MODAL POP-UP: ยืนยันการลบแพ็กเกจคูปอง -->
    <!-- ========================================================= -->
    <div id="deleteCouponModal" class="modal-backdrop fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="modal-content bg-[#FDFBF7] rounded-2xl p-6 w-full max-w-sm shadow-2xl border border-[#E8E2D5] text-center">
            <div class="w-12 h-12 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>

            <h3 class="text-xl font-bold text-[#382214] mb-2">ยืนยันการลบข้อมูล?</h3>
            <p class="text-sm text-gray-600 mb-6">คุณกำลังจะลบแพ็กเกจ <span id="delete_coupon_name" class="font-bold text-red-600"></span> ข้อมูลนี้เมื่อลบแล้วจะไม่สามารถฟื้นคืนได้</p>

            <form action="dashboard.php?tab=coupons" method="POST" class="flex justify-center gap-3">
                <input type="hidden" name="coupon_action" value="delete">
                <input type="hidden" id="delete_coupon_id" name="coupon_id">
                <button type="button" onclick="closeModal('deleteCouponModal')"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl text-sm font-medium hover:bg-gray-300 transition-colors">
                    ยกเลิก
                </button>
                <button type="submit"
                        class="px-5 py-2 bg-red-600 text-white rounded-xl text-sm font-medium hover:bg-red-700 transition-colors shadow">
                    ยืนยัน
                </button>
            </form>
        </div>
    </div>

</section>

<script>
function openEditCouponModal(btn) {
    const id        = btn.getAttribute('data-id');
    const name      = btn.getAttribute('data-name');
    const serviceId = btn.getAttribute('data-service-id');
    const price     = btn.getAttribute('data-price');
    const leaves    = btn.getAttribute('data-leaves');

    document.getElementById('edit_coupon_id').value     = id;
    document.getElementById('edit_coupon_name').value   = name;
    document.getElementById('edit_service_id').value   = serviceId;
    document.getElementById('edit_coupon_price').value  = price;
    document.getElementById('edit_total_leaves').value = leaves;

    openModal('editCouponModal');
}

function openDeleteCouponModal(id, name) {
    document.getElementById('delete_coupon_id').value     = id;
    document.getElementById('delete_coupon_name').textContent = name;

    openModal('deleteCouponModal');
}
</script>