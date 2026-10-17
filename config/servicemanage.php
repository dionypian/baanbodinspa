<?php
// --- ACTION 1: ระบุหมอนวดให้คิวจอง ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign_staff') {
    $booking_id = $_POST['booking_id'] ?? null;
    $staff_id   = !empty($_POST['staff_id']) ? $_POST['staff_id'] : null;

    if ($booking_id && $staff_id) {
        try {
            $pdo->beginTransaction();

            // ดึง booking_group_id ของคิวนี้
            $stmt_get = $pdo->prepare("SELECT booking_group_id FROM bookings WHERE booking_id = :id OR id = :id");
            $stmt_get->execute([':id' => $booking_id]);
            $group_id = $stmt_get->fetchColumn();

            // อัปเดต staff_id
            $stmt_update = $pdo->prepare("UPDATE bookings SET staff_id = :staff_id WHERE booking_id = :id OR id = :id");
            $stmt_update->execute([
                ':staff_id' => $staff_id,
                ':id'       => $booking_id
            ]);

            // เช็คว่าในกลุ่มยังมีคนที่ยังไม่ระบุหมออีกไหม
            $stmt_check = $pdo->prepare("
                SELECT COUNT(*) 
                FROM bookings 
                WHERE booking_group_id = :group_id 
                AND (staff_id IS NULL OR staff_id = 0)
            ");
            $stmt_check->execute([':group_id' => $group_id]);
            $unassigned_count = $stmt_check->fetchColumn();

            if ($unassigned_count == 0) {
                $stmt_confirm = $pdo->prepare("
                    UPDATE bookings 
                    SET status = 'confirmed' 
                    WHERE booking_group_id = :group_id
                ");
                $stmt_confirm->execute([':group_id' => $group_id]);
                $msg = "เลือกพนักงานครบทุกคนแล้ว! สถานะกลุ่มถูกเปลี่ยนเป็น 'ยืนยันแล้ว (confirmed)'";
            } else {
                $msg = "บันทึกพนักงานเรียบร้อย (ยังเหลือคิวที่ต้องเลือกพนักงานในกลุ่มนี้อีก " . $unassigned_count . " รายการ)";
            }

            $pdo->commit();
            $msg_type = "success";

        } catch (PDOException $e) {
            $pdo->rollBack();
            $msg = "เกิดข้อผิดพลาดจาก Database: " . $e->getMessage();
            $msg_type = "error";
        }
    } else {
        $msg = "กรุณาเลือกพนักงานนวดให้ถูกต้อง";
        $msg_type = "error";
    }
}
?>

<!-- TAB 3: ส่วนจัดการบริการ -->
<section id="section-service" class="space-y-6 hidden">
    <div class="flex justify-between items-center">
        <h3 class="text-2xl font-bold text-[#382214]">รายการบริการสปาและนวด</h3>
        <button onclick="openModal('addServiceModal')" class="bg-[#047857] hover:bg-[#065f46] text-white px-4 py-2.5 rounded-xl font-medium shadow transition-all flex items-center gap-2">
            <span>+ เพิ่มบริการใหม่</span>
        </button>
    </div>

    <!-- ตารางรายการบริการ -->
    <div class="bg-white rounded-2xl shadow-sm border border-[#E8E2D5] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-lg text-gray-600">
                <thead class="bg-[#FDFBF7] text-[#382214] font-semibold border-b border-[#E8E2D5]">
                    <tr>
                        <th class="p-4">ลำดับ</th>
                        <th class="p-4">หมวดหมู่</th>
                        <th class="p-4">กลุ่มบริการ</th>
                        <th class="p-4">ชื่อบริการ</th>
                        <th class="p-4">ระยะเวลา</th>
                        <th class="p-4">ราคา</th>
                        <th class="p-4">ค่ามือหมอ</th>
                        <th class="p-4">สถานะ</th>
                        <th class="p-4 text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php
                    // 🟢 ดึงข้อมูลผ่าน PDO ของหน้าหลัก
                    $stmt_srv = $pdo->query("SELECT * FROM allservices ORDER BY category ASC, group_name ASC");
                    $services_list = $stmt_srv->fetchAll(PDO::FETCH_ASSOC);

                    if (!empty($services_list)):
                        $s_idx = 1;
                        foreach ($services_list as $s_row):
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="p-4 font-medium"><?= $s_idx++ ?></td>
                                <td class="p-4 font-semibold text-[#382214]">
                                    <span class="px-2.5 py-1 text-[#382214]">
                                        <?= htmlspecialchars($s_row['category'] ?: '-') ?>
                                    </span>
                                </td>
                                <td class="p-4 font-semibold text-[#047857]"><?= htmlspecialchars($s_row['group_name'] ?? '-') ?></td>
                                <td class="p-4 text-gray-500 max-w-xs truncate"><?= htmlspecialchars($s_row['name'] ?: '-') ?></td>
                                <td class="p-4"><?= $s_row['duration'] ?> นาที</td>
                                <td class="p-4 font-semibold text-[#382214]"><?= number_format($s_row['price']) ?> บาท</td>
                                <td class="p-4 font-semibold text-[#382214]"><?= number_format($s_row['commission'] ?? 0) ?> บาท</td>
                                <td class="p-4">    
                                    <?php if (($s_row['status'] ?? 'active') == 'active'): ?>
                                        <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 rounded-full text-ml font-medium">เปิดให้บริการ</span>
                                    <?php else: ?>
                                        <span class="px-2.5 py-1 bg-gray-100 text-gray-600 rounded-full text-ml font-medium">ปิดชั่วคราว</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-center">
                                    <div class="flex justify-center items-center gap-2">
                                        <button
                                            type="button"
                                            onclick='openEditServiceModal(<?= json_encode($s_row, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                            class="px-3 py-1 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-ml font-medium transition-colors"
                                        >
                                            แก้ไข
                                        </button>
                                        <button
                                            type="button"
                                            onclick="openDeleteServiceModal(<?= $s_row['id'] ?>, '<?= htmlspecialchars($s_row['group_name'] ?? $s_row['name'], ENT_QUOTES, 'UTF-8') ?>')"
                                            class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded-lg text-ml font-medium transition-colors"
                                        >
                                            ลบ
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php
                        endforeach;
                    else:
                        ?>
                        <tr>
                            <td colspan="9" class="p-6 text-center text-gray-400">ยังไม่มีรายการบริการในระบบ</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="assignStaffModal" class="modal-backdrop fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="modal-content bg-[#FDFBF7] rounded-2xl p-6 w-full max-w-md shadow-2xl border border-[#E8E2D5]">
            <div class="flex justify-between items-center pb-3 border-b border-[#E8E2D5] mb-4">
                <h3 class="text-xl font-bold text-[#382214]">จัดสรรพนักงานนวด</h3>
                <button onclick="closeModal('assignStaffModal')" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
            </div>
                
            <form action="assignstaff.php" method="POST" class="space-y-4">
                <input type="hidden" id="assign_booking_id" name="booking_id">

                <div>
                    <label class="block text-sm font-semibold text-[#382214] mb-1">บริการที่จอง</label>
                    <input type="text" id="assign_service_name" readonly class="w-full border border-gray-200 bg-gray-100 rounded-xl p-2.5 text-sm text-gray-600">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-[#382214] mb-1">วันที่และเวลาจอง</label>
                    <input type="text" id="assign_booking_datetime" readonly class="w-full border border-gray-200 bg-gray-100 rounded-xl p-2.5 text-sm text-gray-600">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-[#382214] mb-1">เลือกพนักงานนวด <span class="text-red-500">*</span></label>
                    <select id="assign_staff_select" name="staff_id" required class="w-full border border-amber-300 bg-amber-50 rounded-xl p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#047857]">
                        <option value="">-- กรุณาเลือกพนักงาน --</option>
                        <!-- ตัวเลือกหมอนวดที่ว่างจะถูกโหลดเข้าที่นี่ผ่าน JS -->
                    </select>
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" onclick="closeModal('assignStaffModal')" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl text-sm font-medium hover:bg-gray-300 transition-colors">ยกเลิก</button>
                    <button type="submit" class="px-5 py-2 bg-[#047857] text-white rounded-xl text-sm font-medium hover:bg-[#244735] transition-colors">บันทึกจัดสรร</button>
                </div>
            </form>
        </div>
    </div>

    <div id="addServiceModal" class="modal-backdrop fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="modal-content bg-[#FDFBF7] rounded-2xl p-6 w-full max-w-md shadow-2xl border border-[#E8E2D5]">
            <div class="flex justify-between items-center pb-3 border-b border-[#E8E2D5] mb-4">
                <h3 class="text-xl font-bold text-[#382214]">เพิ่มบริการใหม่</h3>
                <button onclick="closeModal('addServiceModal')" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
            </div>
            <form action="manageservice.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="add">
                    
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold text-[#382214] mb-1">หมวดหมู่ <span class="text-red-500">*</span></label>
                        <select id="add_service_category" name="category" required class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#047857]">
                            <option value="นวด">นวด</option>
                            <option value="สปา">สปา</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-[#382214] mb-1">กลุ่มบริการ <span class="text-red-500">*</span></label>
                        <input type="text" id="add_service_group_name" name="group_name" required class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#047857]">
                    </div>
                </div>
                    
                <div>
                    <label class="block text-sm font-semibold text-[#382214] mb-1">ชื่อบริการ</label>
                    <input type="text" id="add_service_name" name="name" rows="2" class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#047857]">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-[#382214] mb-1">รายละเอียดบริการ</label>
                    <textarea id="add_service_description" name="description" rows="2" class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#047857]"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold text-[#382214] mb-1">ระยะเวลา (นาที)</label>
                        <input type="number" id="add_service_duration" name="duration_minutes" required step="15" class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#047857]">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-[#382214] mb-1">ราคา (บาท)</label>
                        <input type="number" id="add_service_price" name="price" step="0.01" required class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#047857]">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-[#382214] mb-1">ค่ามือหมอ (บาท)</label>
                    <input type="number" id="add_service_commission" name="commission" step="1" min="0" required class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#047857]">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-[#382214] mb-1">สถานะ</label>
                    <select id="add_service_status" name="status" class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#047857]">
                        <option value="active">เปิดให้บริการ (Active)</option>
                        <option value="inactive">ปิดชั่วคราว (Inactive)</option>
                    </select>
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" onclick="closeModal('addServiceModal')" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl text-sm font-medium hover:bg-gray-300 transition-colors">ยกเลิก</button>
                    <button type="submit" class="px-5 py-2 bg-[#047857] text-white rounded-xl text-sm font-medium hover:bg-[#244735] transition-colors">บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editServiceModal" class="modal-backdrop fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="modal-content bg-[#FDFBF7] rounded-2xl p-6 w-full max-w-md shadow-2xl border border-[#E8E2D5]">
            <div class="flex justify-between items-center pb-3 border-b border-[#E8E2D5] mb-4">
                <h3 class="text-xl font-bold text-[#382214]">แก้ไขข้อมูลบริการ</h3>
                <button onclick="closeModal('editServiceModal')" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
            </div>
            <form action="manageservice.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_service_id" name="id">

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold text-[#382214] mb-1">หมวดหมู่ <span class="text-red-500">*</span></label>
                        <select id="edit_service_category" name="category" required class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#047857]">
                            <option value="นวด">นวด</option>
                            <option value="สปา">สปา</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-[#382214] mb-1">กลุ่มบริการ <span class="text-red-500">*</span></label>
                        <input type="text" id="edit_service_group_name" name="group_name" required class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#047857]">
                    </div>
                </div>
                    
                <div>
                    <label class="block text-sm font-semibold text-[#382214] mb-1">ชื่อบริการ</label>
                    <input type="text" id="edit_service_name" name="name" rows="2" class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#047857]">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-[#382214] mb-1">รายละเอียดบริการ</label>
                    <textarea id="edit_service_description" name="description" rows="2" class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#047857]"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold text-[#382214] mb-1">ระยะเวลา (นาที)</label>
                        <input type="number" id="edit_service_duration" name="duration" required step="15" class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#047857]">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-[#382214] mb-1">ราคา (บาท)</label>
                        <input type="number" id="edit_service_price" name="price" step="0.01" required class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#047857]">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-[#382214] mb-1">ค่ามือหมอ (บาท)</label>
                    <input type="number" id="edit_service_commission" name="commission" step="1" min="0" required class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#047857]">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-[#382214] mb-1">สถานะ</label>
                    <select id="edit_service_status" name="status" class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#047857]">
                        <option value="active">เปิดให้บริการ (Active)</option>
                        <option value="inactive">ปิดชั่วคราว (Inactive)</option>
                    </select>
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" onclick="closeModal('editServiceModal')" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl text-sm font-medium hover:bg-gray-300 transition-colors">ยกเลิก</button>
                    <button type="submit" class="px-5 py-2 bg-[#047857] text-white rounded-xl text-sm font-medium hover:bg-[#244735] transition-colors">บันทึกการแก้ไข</button>
                </div>

            </form>
        </div>
    </div>

    <div id="deleteServiceModal" class="modal-backdrop fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="modal-content bg-[#FDFBF7] rounded-2xl p-6 w-full max-w-sm shadow-2xl border border-[#E8E2D5] text-center">
            <div class="w-12 h-12 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-[#382214] mb-2">ยืนยันการลบบริการ?</h3>
            <p class="text-sm text-gray-600 mb-6">คุณกำลังจะลบบริการ <span id="delete_service_name" class="font-bold text-red-600"></span></p>
            <form action="manageservice.php" method="POST" class="flex justify-center gap-3">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" id="delete_service_id" name="service_id">
                <button type="button" onclick="closeModal('deleteServiceModal')" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl text-sm font-medium hover:bg-gray-300">ยกเลิก</button>
                <button type="submit" class="px-5 py-2 bg-red-600 text-white rounded-xl text-sm font-medium hover:bg-red-700">ยืนยันลบ</button>
            </form>
        </div>
    </div>
</section>
<script>
    function openEditServiceModal(service) {
        const idEl          = document.getElementById('edit_service_id');
        const nameEl        = document.getElementById('edit_service_name');
        const catEl         = document.getElementById('edit_service_category');
        const groupEl       = document.getElementById('edit_service_group_name');
        const durEl         = document.getElementById('edit_service_duration');
        const priceEl       = document.getElementById('edit_service_price');
        const commEl        = document.getElementById('edit_service_commission');
        const descEl        = document.getElementById('edit_service_description');
        const statusEl      = document.getElementById('edit_service_status');

        if (idEl)     idEl.value     = service.id || '';
        if (nameEl)   nameEl.value   = service.name || '';
        if (catEl)    catEl.value    = service.category || '';
        if (groupEl)  groupEl.value  = service.group_name || '';
        if (durEl)    durEl.value    = service.duration || '';
        if (priceEl)  priceEl.value  = service.price || '';
        if (commEl)   commEl.value   = service.commission ?? 0;
        if (descEl)   descEl.value   = service.description || '';
        if (statusEl) statusEl.value = service.status || 'active';

        openModal('editServiceModal');
    }

    function openDeleteServiceModal(serviceId, serviceName) {
        const idInput   = document.getElementById('delete_service_id');
        const nameLabel = document.getElementById('delete_service_name');

        if (idInput)   idInput.value = serviceId;
        if (nameLabel) nameLabel.textContent = serviceName;

        openModal('deleteServiceModal');
    }
</script>