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
        <span class="text-sm font-bold text-[#382214] flex items-center gap-1.5">
            เลือกวันที่ดูคิว:
        </span>
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
        <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white text-sm px-2.5 py-1 rounded-lg transition-colors">
            ค้นหา
        </button>
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
                            $total_price = (float) array_sum(array_column($items, 'price'));
                        } elseif (!empty($b['default_price'])) {
                            $total_price = (float) $b['default_price'];
                        } elseif (!empty($b['price'])) {
                            $total_price = (float) $b['price'];
                        }

                        $total_duration = !empty($items)
                            ? array_sum(array_column($items, 'duration'))
                            : ($b['default_duration'] ?? 60);

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
                                    <ul class="list-disc list-inside text-base space-y-0.5">
                                        <?php foreach ($items as $item): ?>
                                            <li><?= htmlspecialchars($item['service_name']) ?> (<?= $item['duration'] ?> นาที)</li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php elseif (!empty($b['default_service_name'])): ?>
                                    <div class="text-base font-medium text-gray-800">
                                        <?= htmlspecialchars($b['default_service_name']) ?> (<?= $b['default_duration'] ?> นาที)
                                    </div>
                                <?php else: ?>
                                    <span class="text-base text-gray-400">ไม่ระบุบริการ</span>
                                <?php endif; ?>
                                
                                <!-- แสดงช่องทางชำระเงินเล็กๆ เมื่อสำเร็จแล้ว -->
                                <?php if ($b['status'] === 'completed' && !empty($b['payment_method'])): ?>
                                    <div class="mt-1 text-[14px] text-gray-500">
                                        ชำระโดย: <span class="font-semibold uppercase text-amber-800"><?= $b['payment_method'] ?></span>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <!-- คอลัมน์หมอนวด -->
                            <td class="p-3.5 text-lg font-bold">
                                <?php if (!empty($b['staff_nickname'])): ?>
                                    <span class="px-2.5 py-1">
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
                                    <span class="px-2.5 py-1 bg-amber-100 text-amber-800 rounded-full text-base font-semibold">Confirmed</span>
                                <?php elseif ($b['status'] === 'completed'): ?>
                                    <span class="px-2.5 py-1 bg-green-100 text-green-800 rounded-full text-base font-semibold">Completed</span>
                                <?php elseif ($b['status'] === 'cancelled'): ?>
                                    <span class="px-2.5 py-1 bg-red-100 text-red-800 rounded-full text-base font-semibold">Cancelled</span>
                                <?php else: ?>
                                    <span class="px-2.5 py-1 bg-gray-100 text-gray-800 rounded-full text-base font-semibold"><?= ucfirst($b['status']) ?></span>
                                <?php endif; ?>
                            </td>

                            <td class="p-3.5 text-center space-x-1">
                                <!-- เงื่อนไข 1: แสดงปุ่มแก้ไขเฉพาะกรณีที่สถานะยังไม่สำเร็จ (completed) -->
                                <?php if ($b['status'] !== 'completed' && $b['status'] !== 'cancelled'): ?>
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
                                <?php endif; ?>

                                <!-- เงื่อนไข 2: กรณีชำระเงินโอนเรียบร้อยแล้ว (completed + transfer) ให้แสดงปุ่มอัปโหลดสลิปแทน -->
                                <?php if ($b['status'] === 'completed' && ($b['payment_method'] ?? '') === 'transfer'): ?>
                                    <button
                                        type="button"
                                        onclick="openUploadSlipModal(<?= $b_id ?>, '<?= htmlspecialchars($b['slip_image'] ?? '') ?>')"
                                        class="px-3 py-1 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-ml font-medium transition-colors flex items-center gap-1 inline-flex"
                                    >
                                        <span><?= !empty($b['slip_image']) ? 'ดู/เปลี่ยนสลิป' : 'อัปโหลดสลิป' ?></span>
                                    </button>
                                <?php endif; ?>

                                <?php if ($b['status'] === 'confirmed'): 
                                    $service_names_str = !empty($items) 
                                        ? implode(', ', array_column($items, 'service_name')) 
                                        : ($b['default_service_name'] ?? 'ไม่ระบุบริการ');
                                ?>
                                    <button
                                        type="button"
                                        onclick="openCompleteModal(
                                            <?= $b_id ?>, 
                                            <?= $total_price ?>, 
                                            '<?= htmlspecialchars($service_names_str, ENT_QUOTES, 'UTF-8') ?>',
                                            '<?= htmlspecialchars($b['payment_method'] ?? '', ENT_QUOTES, 'UTF-8') ?>',
                                            '<?= htmlspecialchars($b['coupon_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>'
                                        )"
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

            <form action="admin-completebook.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" id="complete_booking_id" name="booking_id">

                <div class="bg-amber-50 p-3 rounded-xl">
                    <div class="text-base text-gray-600">ยอดชำระทั้งสิ้น</div>
                    <div class="text-3xl font-bold text-[#047857]"><span id="complete_total_price">0</span> บาท</div>
                </div>

                <div class="text-left">
                    <label class="block text-sm font-semibold mb-1">ช่องทางชำระเงิน:</label>
                    <select name="payment_method" id="payment_method_select" onchange="handlePaymentMethodChange()" required class="w-full border p-2.5 rounded-xl text-sm bg-white">
                        <option value="cash">เงินสด</option>
                        <option value="transfer">โอนเงิน (PromptPay QR)</option>
                        <option value="credit">เครดิต</option>
                        <option value="coupon">คูปอง</option>
                    </select>
                </div>

                <!-- ส่วนแสดง QR Code สำหรับสแกนจ่าย -->
                <div id="qr_code_wrapper" class="hidden my-4 p-4 bg-gray-50 rounded-2xl border border-gray-200 text-center space-y-3">
                    <p class="text-base font-bold text-gray-600">สแกน QR Code เพื่อชำระเงิน</p>
                    <div class="flex justify-center">
                        <img id="promptpay_qr_img" src="" alt="PromptPay QR Code" class="w-48 h-48 rounded-lg shadow-md bg-white p-2 border border-gray-100">
                    </div>
                    <p class="hidden">ยอดเงิน: <span id="qr_amount_text">0</span> บาท</p>
                    <p class="text-sm text-amber-700 font-semibold">บริการ: <span id="qr_service_text" class="text-gray-800 font-normal">-</span></p>
                    
                    <!-- เพิ่มฟิลด์อัปโหลดสลิปย่อยในหน้ายืนยันการชำระเงิน -->
                    <div class="text-left pt-2 border-t border-gray-200">
                        <label class="block text-sm font-bold text-gray-700 mb-1">แนบสลิปการโอน :</label>
                        <input type="file" name="payment_slip" accept="image/*" class="w-full text-xs text-gray-500 border border-gray-300 rounded-lg p-1.5 bg-white">
                    </div>
                </div>

                <div id="coupon_code_wrapper" class="hidden text-left">
                    <label class="block text-sm font-bold text-gray-700 mb-1">รหัสคูปอง (Coupon Code):</label>
                    <input
                        type="text"
                        id="coupon_code_input"
                        name="coupon_code"
                        class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm uppercase bg-gray-100 text-gray-700 font-semibold focus:outline-none cursor-not-allowed"
                        readonly
                    >
                    <p class="text-xs text-amber-800 mt-1">*คูปองถูกผูกกับรายการจองนี้อัตโนมัติ ไม่สามารถแก้ไขได้*</p>
                </div>

                <div class="flex gap-2 pt-2">
                    <button type="button" onclick="closeModal('completeModal')" class="w-1/2 py-2.5 bg-gray-200 text-gray-700 rounded-xl text-sm font-medium">ยกเลิก</button>
                    <button type="submit" class="w-1/2 py-2.5 bg-[#047857] hover:bg-[#065f46] text-white rounded-xl text-sm font-bold shadow-md">ยืนยันรับชำระ</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 2.1 MODAL UPLOAD SLIP (สำหรับอัปโหลดสลิปย้อนหลัง) -->
    <div id="uploadSlipModal" class="modal-backdrop">
        <div class="modal-content bg-white p-6 rounded-2xl max-w-sm w-full text-center shadow-2xl">
            <h3 class="text-xl font-bold text-[#382214] mb-1">สลิปการชำระเงิน</h3>
            <p class="text-base text-gray-500 mb-4">การจองหมายเลข #<span id="upload_slip_booking_id_text"></span></p>

            <form action="admin-uploadslip.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" id="upload_slip_booking_id" name="booking_id">

                <!-- ตัวอย่างรูปภาพเดิม (ถ้ามี) -->
                <div id="current_slip_preview_wrapper" class="hidden text-center">
                    <p class="text-sm font-semibold text-gray-500 mb-1">สลิปปัจจุบัน:</p>
                    <img id="current_slip_img" src="" alt="Slip Preview" class="w-68 max-h-68 object-contain mx-auto rounded-lg border border-gray-200 p-1 shadow-sm mb-3">
                </div>

                <div class="text-left">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">เลือกไฟล์รูปภาพสลิป:</label>
                    <input type="file" name="payment_slip" accept="image/*" required class="w-full text-sm text-gray-500 border border-gray-300 rounded-xl p-2 bg-amber-50/30">
                </div>

                <div class="flex gap-2 pt-2">
                    <button type="button" onclick="closeModal('uploadSlipModal')" class="w-1/2 py-2.5 bg-gray-200 text-gray-700 rounded-xl text-sm font-medium">ปิด</button>
                    <button type="submit" class="w-1/2 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-sm font-bold shadow-md">บันทึกสลิป</button>
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

                <!-- 2. เลือกวัน เวลา และจำนวนผู้ใช้บริการ -->
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

                <!-- 3. เลือกบริการและพนักงาน -->
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
    
    const PromptPay_ID = "0966343365";

    function openCompleteModal(bookingId, totalPrice, serviceNames, paymentMethod, couponCode) {
        document.getElementById('complete_booking_id').value = bookingId;
        document.getElementById('complete_booking_id_text').innerText = bookingId;
        document.getElementById('complete_total_price').innerText = Number(totalPrice).toLocaleString();
        
        const serviceSpan = document.getElementById('qr_service_text');
        if (serviceSpan) {
            serviceSpan.innerText = serviceNames || '-';
        }

        const modal = document.getElementById('completeModal');
        modal.dataset.totalPrice = totalPrice;

        const select = document.getElementById('payment_method_select');
        const couponInput = document.getElementById('coupon_code_input');

        // ถ้ามีการใช้คูปอง หรือมีรหัสคูปองผูกมา
        if (paymentMethod === 'coupon' || (couponCode && couponCode.trim() !== '')) {
            select.value = 'coupon';
            if (couponInput) {
                couponInput.value = couponCode || '';
            }
        } else {
            select.value = 'cash';
            if (couponInput) {
                couponInput.value = '';
            }
        }

        handlePaymentMethodChange();
        openModal('completeModal');
    }

    function handlePaymentMethodChange() {
        const select = document.getElementById('payment_method_select');
        const qrWrapper = document.getElementById('qr_code_wrapper');
        const couponWrapper = document.getElementById('coupon_code_wrapper');
        const modal = document.getElementById('completeModal');
        
        const totalPrice = modal.dataset.totalPrice || 0;

        // จัดการแสดงผลช่องคูปอง
        if (select.value === 'coupon') {
            couponWrapper.classList.remove('hidden');
            // เนื่องจากเป็นคูปองที่ลูกค้าจองมาแล้ว ไม่ต้องบังคับกรอกใหม่ (เพราะใส่ค่า readonly ไว้แล้ว)
            document.getElementById('coupon_code_input').removeAttribute('required');
        } else {
            couponWrapper.classList.add('hidden');
            document.getElementById('coupon_code_input').value = '';
        }

        // จัดการแสดงผล QR โอนเงิน
        if (select.value === 'transfer') {
            qrWrapper.classList.remove('hidden');
            document.getElementById('qr_amount_text').innerText = Number(totalPrice).toLocaleString();
            
            const qrUrl = `https://promptpay.io/${PromptPay_ID}/${totalPrice}.png`;
            document.getElementById('promptpay_qr_img').src = qrUrl;
        } else {
            qrWrapper.classList.add('hidden');
            document.getElementById('promptpay_qr_img').src = '';
        }
    }

    function openUploadSlipModal(bookingId, slipImage) {
        document.getElementById('upload_slip_booking_id').value = bookingId;
        document.getElementById('upload_slip_booking_id_text').innerText = bookingId;

        const previewWrapper = document.getElementById('current_slip_preview_wrapper');
        const slipImg = document.getElementById('current_slip_img');

        if (slipImage && slipImage !== '') {
            slipImg.src = 'uploads/slips/' + slipImage;
            previewWrapper.classList.remove('hidden');
        } else {
            previewWrapper.classList.add('hidden');
            slipImg.src = '';
        }

        openModal('uploadSlipModal');
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