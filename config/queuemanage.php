<?php
// --- ACTION 2: แอดมินสร้างคิวจองเอง (Walk-in / โทรจอง) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'admin_create_booking') {
    $cus_name       = trim($_POST['cus_name'] ?? '');
    $cus_phone      = trim($_POST['cus_phone'] ?? '');
    $booking_date   = $_POST['booking_date'] ?? '';
    $booking_time   = $_POST['booking_time'] ?? '';
    $customer_count = $_POST['customer_count'] ?? 1;
    $service_id     = $_POST['service_id'] ?? null;
    $staff_id       = !empty($_POST['staff_id']) ? $_POST['staff_id'] : null;

    if (!empty($cus_name) && !empty($cus_phone) && !empty($booking_date) && !empty($booking_time) && !empty($service_id)) {
        try {
            $pdo->beginTransaction();

            // 1. ตรวจสอบเงื่อนไขเวลาปิดร้าน 23:00 น.
            $stmt_srv = $pdo->prepare("SELECT duration FROM allservices WHERE id = :id");
            $stmt_srv->execute([':id' => $service_id]);
            $duration = $stmt_srv->fetchColumn() ?: 60;

            $start_timestamp   = strtotime("$booking_date $booking_time");
            $end_timestamp     = $start_timestamp + ($duration * 60);
            $closing_timestamp = strtotime("$booking_date 23:00:00");

            if ($end_timestamp > $closing_timestamp) {
                throw new Exception("เวลาสิ้นสุดบริการเกินเวลาปิดร้าน (23:00 น.) กรุณาเลือกเวลาใหม่");
            }

            // 2. ค้นหา/สร้างข้อมูลลูกค้าด้วยเบอร์โทรศัพท์
            $stmt_cus = $pdo->prepare("SELECT cus_id FROM customers WHERE phone = :phone LIMIT 1");
            $stmt_cus->execute([':phone' => $cus_phone]);
            $cus_id = $stmt_cus->fetchColumn();

            if (!$cus_id) {
                $stmt_ins_cus = $pdo->prepare("INSERT INTO customers (name, phone) VALUES (:name, :phone)");
                $stmt_ins_cus->execute([':name' => $cus_name, ':phone' => $cus_phone]);
                $cus_id = $pdo->lastInsertId();
            }

            // 3. กำหนดสถานะ
            $booking_group_id = 'WALK-' . date('YmdHis') . '-' . rand(100, 999);
            $status = !empty($staff_id) ? 'confirmed' : 'pending';

            // 4. บันทึกลงตาราง bookings
            $stmt_ins_b = $pdo->prepare("
                INSERT INTO bookings (
                    booking_group_id, 
                    cus_id, 
                    staff_id, 
                    customer_count, 
                    booking_date, 
                    booking_time, 
                    status, 
                    created_at
                ) VALUES (
                    :booking_group_id, 
                    :cus_id, 
                    :staff_id, 
                    :customer_count, 
                    :booking_date, 
                    :booking_time, 
                    :status, 
                    NOW()
                )
            ");

            $stmt_ins_b->execute([
                ':booking_group_id' => $booking_group_id,
                ':cus_id'           => $cus_id,
                ':staff_id'         => $staff_id,
                ':customer_count'   => $customer_count,
                ':booking_date'     => $booking_date,
                ':booking_time'     => $booking_time,
                ':status'           => $status
            ]);

            $new_booking_id = $pdo->lastInsertId();

            // 5. บันทึกลงตาราง booking_items
            $stmt_ins_item = $pdo->prepare("INSERT INTO booking_items (booking_id, service_id) VALUES (:b_id, :s_id)");
            $stmt_ins_item->execute([
                ':b_id' => $new_booking_id,
                ':s_id' => $service_id
            ]);

            $pdo->commit();
            $msg = "บันทึกการจองสำหรับแอดมินเรียบร้อยแล้ว! (รหัสกลุ่ม: {$booking_group_id})";
            $msg_type = "success";

        } catch (Exception $e) {
            $pdo->rollBack();
            $msg = "เกิดข้อผิดพลาด: " . $e->getMessage();
            $msg_type = "error";
        }
    } else {
        $msg = "กรุณากรอกข้อมูลให้ครบถ้วน";
        $msg_type = "error";
    }
}
?>

<!-- TAB 2: ส่วนจัดการคิวจอง (Default Open) -->
<section id="section-queue" class="space-y-6">
    <div class="flex justify-between items-center">
        <h3 class="text-2xl font-bold text-[#382214]">รายการจองคิวทั้งหมด</h3>
        <a
            href="admin-booking.php"
            class="bg-[#047857] hover:bg-[#065f46] text-white px-4 py-2.5 rounded-xl font-medium shadow transition-all flex items-center gap-2"
        >
            <span>+ จองคิวใหม่</span>
        </a>
    </div>
    <form id="dateFilterForm" method="GET" action="dashboard.php" class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
        <input type="hidden" name="tab" value="queue">

        <div class="flex items-center gap-3">
            <span class="text-sm font-bold text-[#382214] flex items-center gap-1.5">
                เลือกวันที่ดูคิว:
            </span>
            <form id="dateFilterForm" method="GET" action="dashboard.php" class="flex items-center gap-2">
                <input type="hidden" name="tab" value="queue">
                <input
                    type="text"
                    id="queue_date_picker"
                    name="filter_date"
                    value="<?= htmlspecialchars($selected_date) ?>"
                    class="bg-white border border-gray-300 rounded-xl px-3 py-2 text-sm font-semibold text-[#382214] bg-amber-50/50 focus:outline-none focus:ring-2 focus:ring-amber-500 cursor-pointer text-center w-44"
                >
                <input
                    type="text"
                    name="search_query"
                    value="<?= htmlspecialchars($_GET['search_query'] ?? '') ?>"
                    class="border border-gray-300 rounded-xl pl-9 pr-3 py-2 text-sm text-gray-700 bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500"
                >
                <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white text-xs px-2.5 py-1 rounded-lg transition-colors">
                    ค้นหา
                </button>
            </form>
        </div>
    </form>

    <div class="bg-white rounded-2xl shadow-sm border border-[#E8E2D5] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-lg text-gray-600">
                <thead class="bg-[#FDFBF7] text-[#382214] font-semibold border-b border-[#E8E2D5]">
                    <tr>
                        <th class="p-4">รหัสจอง</th>
                        <th class="p-4">วัน-เวลา</th>
                        <th class="p-4">ลูกค้า</th>
                        <th class="p-4">บริการที่เลือก</th>
                        <th class="p-4">หมอนวด</th>
                        <th class="p-4">ราคารวม</th>
                        <th class="p-4">สถานะ</th>
                        <th class="p-4 text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($bookings as $b):
                        $b_id = $b['booking_id'] ?? $b['id'];
                        $items = $booking_items_map[$b_id] ?? [];

                        $total_price = 0.0;

                        if (!empty($items)) {
                            // มีรายการใน booking_items
                            $total_price = (float) array_sum(array_column($items, 'price'));
                        } elseif (!empty($b['default_price'])) {
                            // ไม่มีใน booking_items ให้ใช้ราคาจากบริการตั้งต้น
                            $total_price = (float) $b['default_price'];
                        } elseif (!empty($b['price'])) {
                            // Fallback กรณีใช้คอลัมน์ price ตรงๆ ในตาราง bookings
                            $total_price = (float) $b['price'];
                        }

                        // คำนวณเวลารวม (นาที) สำหรับเช็คหมอว่าง
                        $total_duration = !empty($items)
                            ? array_sum(array_column($items, 'duration'))
                            : ($b['default_duration'] ?? 60);

                        // ดึงรายการหมอว่างสำหรับคิวนี้
                        $available_staffs_for_this_booking = getAvailableStaffs(
                            $pdo,
                            $b['booking_date'],
                            $b['booking_time'],
                            $total_duration,
                            $b_id
                        );
                        ?>
                        <tr class="hover:bg-amber-50/40 transition-colors">
                            <td class="p-3.5 font-bold text-[#382214]">#<?= $b_id ?></td>
                            <td class="p-3.5">
                                <div class="font-semibold"><?= date('d/m/Y', strtotime($b['booking_date'])) ?></div>
                                <div class="text-xs text-gray-500"><?= date('H:i', strtotime($b['booking_time'])) ?> น.</div>
                            </td>
                            <td class="p-3.5">
                                <div class="font-semibold"><?= htmlspecialchars($b['customer_name'] ?? 'Walk-in') ?></div>
                                <div class="text-xs text-gray-500"><?= htmlspecialchars($b['customer_phone'] ?? '-') ?></div>
                            </td>

                            <!-- คอลัมน์บริการที่เลือก -->
                            <td class="p-3.5">
                                <?php if (!empty($items)): ?>
                                    <ul class="list-disc list-inside text-xs space-y-0.5">
                                        <?php foreach ($items as $item): ?>
                                            <li><?= htmlspecialchars($item['service_name']) ?> (<?= $item['duration'] ?> นาที)</li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php elseif (!empty($b['default_service_name'])): ?>
                                    <div class="text-sm font-medium text-gray-800">
                                        <?= htmlspecialchars($b['default_service_name']) ?> (<?= $b['default_duration'] ?> นาที)
                                    </div>
                                <?php else: ?>
                                    <span class="text-xs text-gray-400">- ไม่ระบุบริการ -</span>
                                <?php endif; ?>
                            </td>

                            <!-- คอลัมน์หมอนวด -->
                            <td class="p-3.5 text-xs font-medium">
                                <?php if (!empty($b['staff_nickname'])): ?>
                                    <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 rounded-lg border border-emerald-200">
                                        หมอ<?= htmlspecialchars($b['staff_nickname']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="px-2.5 py-1 bg-amber-50 text-amber-700 rounded-lg border border-amber-200 font-semibold">
                                        ⚠️ ยังไม่เลือกหมอ
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="p-3.5 font-bold text-[#047857]">
                                <?= number_format($total_price) ?> ฿
                            </td>

                            <td class="p-3.5">
                                <?php if ($b['status'] === 'confirmed'): ?>
                                    <span class="px-2.5 py-1 bg-amber-100 text-amber-800 rounded-full text-xs font-semibold">Confirmed</span>
                                <?php elseif ($b['status'] === 'completed'): ?>
                                    <span class="px-2.5 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">Completed</span>
                                <?php elseif ($b['status'] === 'cancelled'): ?>
                                    <span class="px-2.5 py-1 bg-red-100 text-red-800 rounded-full text-xs font-semibold">Cancelled</span>
                                <?php else: ?>
                                    <span class="px-2.5 py-1 bg-gray-100 text-gray-800 rounded-full text-xs font-semibold"><?= ucfirst($b['status']) ?></span>
                                <?php endif; ?>
                            </td>

                            <td class="p-3.5 text-center space-x-1">
                                <!-- ปุ่มแก้ไขรายการบริการและเลือกพนักงาน -->
                                <button
                                    type="button"
                                    onclick='openEditBookingModal(
                                                    <?= htmlspecialchars(json_encode($b), ENT_QUOTES, "UTF-8") ?>, 
                                                    <?= htmlspecialchars(json_encode($items), ENT_QUOTES, "UTF-8") ?>,
                                                    <?= htmlspecialchars(json_encode($available_staffs_for_this_booking), ENT_QUOTES, "UTF-8") ?>
                                                )'
                                    class="px-3 py-1 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-ml font-medium transition-colors"
                                >
                                    แก้ไข
                                </button>

                                <?php if ($b['status'] === 'confirmed'): ?>
                                    <button
                                        type="button"
                                        onclick="openCompleteModal(<?= $b_id ?>, <?= $total_price ?>)"
                                        class="px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-ml font-medium transition-colors"
                                    >
                                        ชำระเงิน
                                    </button>
                                <?php endif; ?>

                                <?php if ($b['status'] !== 'cancelled' && $b['status'] !== 'completed'): ?>
                                    <button
                                        type="button"
                                        onclick='openCancelModal(<?= htmlspecialchars(json_encode($b), ENT_QUOTES, "UTF-8") ?>)'
                                        class="px-3 py-1 bg-red-100 hover:bg-red-200 text-red-700 rounded-lg text-ml font-medium transition-colors"
                                    >
                                        ยกเลิกคิว
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 1. MODAL EDIT BOOKING SERVICES & STAFF -->
    <div id="editBookingModal" class="modal-backdrop">
        <div class="modal-content bg-white p-6 rounded-2xl max-w-lg w-full shadow-2xl">
            <h3 class="text-lg font-bold text-[#382214] mb-3">แก้ไขบริการ / จัดสรรพนักงาน (การจอง #<span id="edit_booking_id_text"></span>)</h3>
                        
            <form action="admin-updatebook.php" method="POST" class="space-y-4 text-left">
                <input type="hidden" id="edit_booking_id" name="booking_id">

                <!-- เลือกพนักงานนวด -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">เลือก / เปลี่ยนพนักงานนวด:</label>
                    <select id="edit_staff_id" name="staff_id" class="w-full border p-2.5 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-amber-500">
                        <option value="">-- ไม่ระบุ (ให้ร้านจัดสรร) --</option>
                        <?php foreach ($staffs as $st): ?>
                            <option value="<?= $st['staff_id'] ?>">
                                หมอ <?= htmlspecialchars($st['nickname'] ?? $st['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- เลือกรายการบริการ -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">เลือกบริการ (ติ๊กเลือกรายการที่ต้องการ):</label>
                    <div class="max-h-56 overflow-y-auto border p-3 rounded-xl space-y-2 bg-gray-50">
                        <?php foreach ($all_services as $srv): ?>
                            <label class="flex items-center gap-2 text-sm bg-white p-2 rounded-lg border border-gray-200 cursor-pointer hover:border-amber-400">
                                <input type="checkbox" name="service_ids[]" value="<?= $srv['id'] ?>" class="srv-checkbox w-4 h-4 text-[#382214]">
                                <span class="flex-grow font-medium text-gray-800"><?= htmlspecialchars($srv['name']) ?></span>
                                <span class="text-xs text-gray-500"><?= $srv['duration'] ?> นาที | <?= number_format($srv['price']) ?>฿</span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeModal('editBookingModal')" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl text-sm font-medium">ยกเลิก</button>
                    <button type="submit" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-sm font-medium shadow-md">บันทึกการแก้ไข</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 2. MODAL COMPLETE PAYMENT -->
    <div id="completeModal" class="modal-backdrop">
        <div class="modal-content bg-white p-6 rounded-2xl max-w-sm w-full text-center shadow-2xl">
            <h3 class="text-lg font-bold text-[#382214] mb-1">ช่องทางการชำระเงิน</h3>
            <p class="text-sm text-gray-500 mb-4">การจองหมายเลข #<span id="complete_booking_id_text"></span></p>

            <form action="admin-completebook.php" method="POST" class="space-y-4">
                <input type="hidden" id="complete_booking_id" name="booking_id">

                <div class="bg-amber-50 p-3 rounded-xl border border-amber-200">
                    <div class="text-base text-gray-600">ยอดชำระทั้งสิ้น</div>
                    <div class="text-3xl font-bold text-[#047857]"><span id="complete_total_price">0</span> บาท</div>
                </div>

                <div class="text-left">
                    <label class="block text-sm font-semibold mb-1">ช่องทางชำระเงิน:</label>
                    <select name="payment_method" id="payment_method_select" onchange="toggleCouponInput()" required class="w-full border p-2.5 rounded-xl text-sm bg-white">
                        <option value="cash">เงินสด</option>
                        <option value="transfer">โอนเงิน</option>
                        <option value="credit">เครดิต</option>
                        <option value="coupon">คูปอง</option>
                    </select>
                </div>

                <div id="coupon_code_wrapper" class="hidden">
                    <label class="block text-sm font-bold text-gray-700 mb-1">รหัสคูปอง (Coupon Code): <span class="text-red-500">*</span></label>
                    <input
                        type="text"
                        id="coupon_code_input"
                        name="coupon_code"
                        class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm uppercase bg-amber-50/50 focus:ring-2 focus:ring-amber-500 focus:outline-none"
                    >
                </div>

                <div class="flex gap-2 pt-2">
                    <button type="button" onclick="closeModal('completeModal')" class="w-1/2 py-2.5 bg-gray-200 text-gray-700 rounded-xl text-sm font-medium">ยกเลิก</button>
                    <button type="submit" class="w-1/2 py-2.5 bg-[#047857] hover:bg-[#065f46] text-white rounded-xl text-sm font-bold shadow-md">ยืนยันรับชำระ</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 3. MODAL CANCEL BOOKING CONFIRMATION -->
    <div id="cancelModal" class="modal-backdrop">
        <div class="modal-content bg-white p-6 rounded-2xl max-w-sm w-full text-center shadow-2xl">
            <div class="w-12 h-12 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-3 text-xl font-bold">
                ⚠️
            </div>
            <h3 class="text-lg font-bold text-[#382214] mb-1">ยืนยันการยกเลิกคิว</h3>
            <p class="text-ml text-gray-500 mb-4">คุณแน่ใจหรือไม่ว่าต้องการยกเลิกคิวจองของ</p>

            <div class="bg-red-50/60 rounded-xl p-3 mb-4 text-left border border-red-100 space-y-1.5 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">รหัสจอง:</span>
                    <span id="cancel_booking_id_text" class="font-bold text-[#382214]"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">ชื่อลูกค้า:</span>
                    <span id="cancel_customer_name_text" class="font-semibold text-gray-800"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">วัน-เวลา:</span>
                    <span id="cancel_datetime_text" class="font-semibold text-gray-800"></span>
                </div>
            </div>

            <form action="admin-cancelbook.php" method="POST" class="space-y-4">
                <input type="hidden" id="cancel_booking_id" name="booking_id">

                <div class="flex gap-2 pt-2">
                    <button type="button" onclick="closeModal('cancelModal')" class="w-1/2 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-xl text-sm font-medium transition-colors">
                        ย้อนกลับ
                    </button>
                    <button type="submit" class="w-1/2 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl text-sm font-bold shadow-md transition-colors">
                        ยืนยันยกเลิก
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: แอดมินจองคิว (Walk-in / โทรจอง) -->
    <div id="adminBookingModal" class="modal-backdrop fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="modal-content bg-[#FDFBF7] rounded-2xl p-6 w-full max-w-xl shadow-2xl border border-[#E8E2D5] max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center pb-3 border-b border-[#E8E2D5] mb-4">
                <h3 class="text-xl font-bold text-[#382214]">📌 จองคิวใหม่ (Walk-in / โทรจอง)</h3>
                <button onclick="closeModal('adminBookingModal')" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
            </div>
                
            <form action="dashboard.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="admin_create_booking">

                <!-- 1. ข้อมูลลูกค้า (สำหรับแอดมินกรอก) -->
                <div class="bg-white p-4 rounded-xl border border-gray-200 space-y-3">
                    <h4 class="font-bold text-sm text-[#382214]">👤 ข้อมูลลูกค้า</h4>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-[#382214] mb-1">ชื่อลูกค้า <span class="text-red-500">*</span></label>
                            <input type="text" name="cus_name" required placeholder="เช่น คุณสมชาย" class="w-full border border-gray-300 rounded-xl p-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#047857]">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[#382214] mb-1">เบอร์โทรศัพท์ <span class="text-red-500">*</span></label>
                            <input type="tel" name="cus_phone" required placeholder="0812345678" class="w-full border border-gray-300 rounded-xl p-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#047857]">
                        </div>
                    </div>
                </div>

                <!-- 2. เลือกวัน เวลา และจำนวนผู้ใช้บริการ (เหมือนฟอร์มลูกค้า) -->
                <div class="bg-white p-4 rounded-xl border border-gray-200 space-y-3">
                    <h4 class="font-bold text-sm text-[#382214]">📅 วันที่และเวลาจอง</h4>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-[#382214] mb-1">วันที่ <span class="text-red-500">*</span></label>
                            <input type="date" name="booking_date" required value="<?= date('Y-m-d') ?>" class="w-full border border-gray-300 rounded-xl p-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#047857]">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[#382214] mb-1">เวลาเริ่ม <span class="text-red-500">*</span></label>
                            <select name="booking_time" required class="w-full border border-gray-300 rounded-xl p-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#047857]">
                                <?php
                                for ($h = 10; $h <= 21; $h++) {
                                    foreach (['00', '30'] as $m) {
                                        $t = sprintf('%02d:%s', $h, $m);
                                        echo "<option value='{$t}'>{$t} น.</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[#382214] mb-1">จำนวนคน</label>
                            <select name="customer_count" class="w-full border border-gray-300 rounded-xl p-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#047857]">
                                <option value="1">1 ท่าน</option>
                                <option value="2">2 ท่าน</option>
                                <option value="3">3 ท่าน</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 3. เลือกบริการและพนักงาน (เหมือนฟอร์มลูกค้า) -->
                <div class="bg-white p-4 rounded-xl border border-gray-200 space-y-3">
                    <h4 class="font-bold text-sm text-[#382214]">💆‍♂️ รายการบริการและพนักงาน</h4>
                        
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-[#382214] mb-1">บริการ <span class="text-red-500">*</span></label>
                            <select name="service_id" required class="w-full border border-gray-300 rounded-xl p-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#047857]">
                                <option value="">-- เลือกรายการบริการ --</option>
                                <?php
                                $stmt_srv = $pdo->query("SELECT id, name, duration, price FROM allservices WHERE status = 'active' ORDER BY id ASC");
                                while ($srv = $stmt_srv->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<option value='{$srv['id']}'>{$srv['name']} ({$srv['duration']} นาที - {$srv['price']} บาท)</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-[#382214] mb-1">เลือกพนักงานนวด</label>
                            <select name="staff_id" class="w-full border border-gray-300 rounded-xl p-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#047857]">
                                <option value="">-- ร้านจัดสรรให้ (ไม่ระบุ) --</option>
                                <?php
                                $stmt_st = $pdo->query("SELECT staff_id, nickname, name FROM staffs ORDER BY staff_id ASC");
                                while ($st = $stmt_st->fetch(PDO::FETCH_ASSOC)) {
                                    $st_name = !empty($st['nickname']) ? $st['nickname'] : $st['name'];
                                    echo "<option value='{$st['staff_id']}'>หมอ {$st_name}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- ปุ่มแอ็กชัน -->
                <div class="flex justify-end gap-3 pt-3 border-t border-[#E8E2D5]">
                    <button type="button" onclick="closeModal('adminBookingModal')" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl text-sm font-medium hover:bg-gray-300 transition-colors">
                        ยกเลิก
                    </button>
                    <button type="submit" class="px-5 py-2 bg-[#047857] text-white rounded-xl text-sm font-medium hover:bg-[#065f46] transition-colors shadow">
                        บันทึกจองคิว
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
<script>
    function openAdminBookingModal() {
        openModal('adminBookingModal');
    }

    function openEditBookingModal(booking, currentItems, availableStaffs) {
        const bookingId = booking.booking_id || booking.id || '';
        
        document.getElementById('edit_booking_id').value = bookingId;
        document.getElementById('edit_booking_id_text').innerText = bookingId;

        const staffSelect = document.getElementById('edit_staff_id');
        if (staffSelect && Array.isArray(availableStaffs)) {
            staffSelect.innerHTML = '<option value="">-- ไม่ระบุ (ให้ร้านจัดสรร) --</option>';

            let currentStaffIncluded = false;

            availableStaffs.forEach(st => {
                const opt = document.createElement('option');
                opt.value = st.staff_id;
                opt.textContent = `หมอ ${st.nickname || st.name}`;
                if (parseInt(st.staff_id) === parseInt(booking.staff_id)) {
                    opt.selected = true;
                    currentStaffIncluded = true;
                }
                staffSelect.appendChild(opt);
            });

            if (booking.staff_id && !currentStaffIncluded) {
                const opt = document.createElement('option');
                opt.value = booking.staff_id;
                opt.textContent = `หมอ ${booking.staff_nickname || booking.staff_name} (หมอประจำคิวปัจจุบัน)`;
                opt.selected = true;
                staffSelect.insertBefore(opt, staffSelect.children[1]);
            }
        }

        const checkboxes = document.querySelectorAll('.srv-checkbox');
        checkboxes.forEach(cb => cb.checked = false);

        if (Array.isArray(currentItems) && currentItems.length > 0) {
            const currentServiceIds = currentItems.map(item => parseInt(item.service_id || item.id));
            checkboxes.forEach(cb => {
                if (currentServiceIds.includes(parseInt(cb.value))) {
                    cb.checked = true;
                }
            });
        } else if (booking.service_id) {
            checkboxes.forEach(cb => {
                if (parseInt(cb.value) === parseInt(booking.service_id)) {
                    cb.checked = true;
                }
            });
        }

        openModal('editBookingModal');
    }

    function openCompleteModal(bookingId, totalPrice) {
        const idInput = document.getElementById('complete_booking_id');
        const textEl  = document.getElementById('complete_booking_id_text');
        const priceEl = document.getElementById('complete_total_price');
        const methodSelect = document.getElementById('payment_method_select');

        if (idInput) idInput.value = bookingId;
        if (textEl)  textEl.innerText = bookingId;
        if (priceEl) priceEl.innerText = Number(totalPrice || 0).toLocaleString();
        if (methodSelect) methodSelect.value = 'cash';
        
        toggleCouponInput();
        openModal('completeModal');
    }

    function openCancelModal(booking) {
        if (!booking) return;

        const bookingId = booking.booking_id || booking.id || '';
        
        const idInput = document.getElementById('cancel_booking_id');
        if (idInput) idInput.value = bookingId;

        const idText = document.getElementById('cancel_booking_id_text');
        if (idText) idText.innerText = `#${bookingId}`;

        const nameText = document.getElementById('cancel_customer_name_text');
        if (nameText) nameText.innerText = booking.customer_name || 'Walk-in';

        const datetimeText = document.getElementById('cancel_datetime_text');
        if (datetimeText && booking.booking_date) {
            const dateParts = booking.booking_date.split('-');
            const formattedDate = dateParts.length === 3 ? `${dateParts[2]}/${dateParts[1]}/${dateParts[0]}` : booking.booking_date;
            const formattedTime = booking.booking_time ? booking.booking_time.substring(0, 5) : '';
            
            datetimeText.innerText = `${formattedDate} เวลา ${formattedTime} น.`;
        }

        openModal('cancelModal');
    }
</script>