<!-- TAB 3: ส่วนจัดการพนักงาน -->
<?php
// 1. ดึงรายการบริการทั้งหมดสำหรับใช้ใน Modal ทักษะ
$conn_srv = new mysqli("localhost", "root", "", "massage_booking");
$conn_srv->set_charset("utf8mb4");

$all_services_list = [];
$res_srv = $conn_srv->query("SELECT id, name, category, group_name FROM allservices WHERE status = 'active' ORDER BY category ASC, group_name ASC, id ASC");
if ($res_srv && $res_srv->num_rows > 0) {
    while ($s = $res_srv->fetch_assoc()) {
        $all_services_list[] = $s;
    }
}

// 2. ดึงทักษะหมอนวดทั้งหมดเตรียมไว้
$staff_skills_map = [];
$res_sk = $conn_srv->query("SELECT staff_id, service_id FROM staff_services");
if ($res_sk && $res_sk->num_rows > 0) {
    while ($sk = $res_sk->fetch_assoc()) {
        $staff_skills_map[$sk['staff_id']][] = (int) $sk['service_id'];
    }
}
$conn_srv->close();
?>

<section
    id="section-staff"
    class="space-y-6 hidden"
    x-data="{ 
                    openSkillsModal: false, 
                    currentStaffId: 0, 
                    currentStaffName: '', 
                    selectedServiceIds: [],
                    staffSkillsMap: <?= htmlspecialchars(json_encode($staff_skills_map), ENT_QUOTES, 'UTF-8') ?>,
                    allServices: <?= htmlspecialchars(json_encode($all_services_list), ENT_QUOTES, 'UTF-8') ?>,

                    get groupedServices() {
                        const groups = {};
                        (this.allServices || []).forEach(srv => {
                            const groupName = srv.group_name || srv.name || 'บริการทั่วไป';
                            if (!groups[groupName]) {
                                groups[groupName] = {
                                    name: groupName,
                                    category: srv.category || '',
                                    services: []
                                };
                            }
                            groups[groupName].services.push(srv);
                        });
                        return Object.values(groups);
                    },

                    isGroupSelected(group) {
                        if (!group.services || group.services.length === 0) return false;
                        return group.services.every(srv => 
                            this.selectedServiceIds.includes(String(srv.id)) || 
                            this.selectedServiceIds.includes(Number(srv.id))
                        );
                    },

                    toggleGroup(group, isChecked) {
                        const serviceIds = group.services.map(srv => srv.id);
                        if (isChecked) {
                            serviceIds.forEach(id => {
                                if (!this.selectedServiceIds.includes(id) && !this.selectedServiceIds.includes(String(id))) {
                                    this.selectedServiceIds.push(id);
                                }
                            });
                        } else {
                            this.selectedServiceIds = this.selectedServiceIds.filter(
                                id => !serviceIds.includes(Number(id)) && !serviceIds.includes(String(id))
                            );
                        }
                    }
                }"
>

    <div class="flex justify-between items-center">
        <h3 class="text-2xl font-bold text-[#382214]">รายชื่อพนักงานทั้งหมด</h3>
        <button onclick="openModal('addStaffModal')" class="bg-[#047857] hover:bg-[#065f46] text-white px-4 py-2.5 rounded-xl font-medium shadow transition-all flex items-center gap-2">
            + เพิ่มพนักงานใหม่
        </button>
    </div>

    <!-- ตารางแสดงรายชื่อพนักงาน -->
    <div class="bg-white rounded-2xl shadow-sm border border-[#E8E2D5] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-base text-gray-600">
                <thead class="bg-[#FDFBF7] text-lg text-[#382214] font-semibold border-b border-[#E8E2D5]">
                    <tr>
                        <th class="p-4">ลำดับ</th>
                        <th class="p-4">ชื่อเล่น</th>
                        <th class="p-4">Username</th>
                        <th class="p-4">เพศ</th>
                        <th class="p-4">วันเกิด</th>
                        <th class="p-4">วันหยุด</th>
                        <th class="p-4">เบอร์โทรศัพท์</th>
                        <th class="p-4 text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php
                    $conn = new mysqli("localhost", "root", "", "massage_booking");
                    $conn->set_charset("utf8mb4");

                    $days_th = [
                        'Monday'    => 'วันจันทร์',
                        'Tuesday'   => 'วันอังคาร',
                        'Wednesday' => 'วันพุธ',
                        'Thursday'  => 'วันพฤหัสบดี',
                        'Friday'    => 'วันศุกร์',
                        'Saturday'  => 'วันเสาร์',
                        'Sunday'    => 'วันอาทิตย์'
                    ];

                    $sql = "SELECT * FROM staffs ORDER BY staff_id ASC";
                    $result = $conn->query($sql);

                    if ($result && $result->num_rows > 0):
                        $i = 1;
                        while ($row = $result->fetch_assoc()):
                            $gender_text = "-";
                            if ($row['gender'] == 'male') $gender_text = "ชาย";
                            if ($row['gender'] == 'female') $gender_text = "หญิง";
                            $day_off_en = $row['day_off'] ?? '';
                            $day_off_th = isset($days_th[$day_off_en]) ? $days_th[$day_off_en] : ($day_off_en ?: '-');
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="p-4"><?= $i++ ?></td>
                                <td class="p-4"><?= htmlspecialchars($row['nickname'] ?: '-') ?></td>
                                <td class="p-4"><span class="bg-gray-100 text-gray-700 font-mono text-sm px-2 py-1 rounded"><?= htmlspecialchars($row['username'] ?? '-') ?></span></td>
                                <td class="p-4"><?= $gender_text ?></td>
                                <td class="p-4"><?= htmlspecialchars($row['birthdate'] ?: '-') ?></td>
                                <td class="p-4"><?= htmlspecialchars($day_off_th) ?></td>
                                <td class="p-4"><?= htmlspecialchars($row['phone']) ?></td>
                                <td class="p-4 text-center space-x-1">
                                    <button
                                        type="button"
                                        @click="
                                                currentStaffId = <?= $row['staff_id'] ?>; 
                                                currentStaffName = '<?= htmlspecialchars($row['nickname'] ?: $row['name'], ENT_QUOTES) ?>'; 
                                                selectedServiceIds = staffSkillsMap[<?= $row['staff_id'] ?>] ? [...staffSkillsMap[<?= $row['staff_id'] ?>]] : []; 
                                                openSkillsModal = true;
                                            "
                                        class="px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-ml font-medium transition-colors"
                                    >
                                        บริการ
                                    </button>

                                    <button
                                        type="button"
                                        data-id="<?= $row['staff_id'] ?>"
                                        data-name="<?= htmlspecialchars($row['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        data-nickname="<?= htmlspecialchars($row['nickname'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        data-username="<?= htmlspecialchars($row['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        data-phone="<?= htmlspecialchars($row['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        data-gender="<?= htmlspecialchars($row['gender'] ?? 'male', ENT_QUOTES, 'UTF-8') ?>"
                                        data-birthdate="<?= htmlspecialchars($row['birthdate'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        data-dayoff="<?= htmlspecialchars($row['day_off'] ?? 'Monday', ENT_QUOTES, 'UTF-8') ?>"
                                        onclick="openEditModal(this)"
                                        class="px-3 py-1 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-ml font-medium transition-colors"
                                    >
                                        แก้ไข
                                    </button>

                                    <button
                                        type="button"
                                        onclick="openDeleteModal(<?= $row['staff_id'] ?>, '<?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?>')"
                                        class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded-lg text-ml font-medium transition-colors"
                                    >
                                        ลบ
                                    </button>
                                </td>
                            </tr>
                            <?php
                        endwhile;
                    else:
                        ?>
                        <tr>
                            <td colspan="8" class="p-4 text-center text-gray-400">ไม่มีข้อมูลพนักงาน</td>
                        </tr>
                        <?php
                    endif;
                    $conn->close();
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL POPUP จัดการทักษะบริการ -->
    <div
        x-show="openSkillsModal"
        x-cloak
        style="display: none;"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4"
    >
        <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[85vh]" @click.outside="openSkillsModal = false">
            <div class="p-5 bg-white text-[#382214] flex justify-between items-center border-b">
                <div>
                    <h3 class="font-bold text-lg">จัดการบริการที่ทำได้</h3>
                    <p class="text-sm text-gray-600">พนักงาน: หมอ <span class="font-bold text-[#382214]" x-text="currentStaffName"></span></p>
                </div>
                <button type="button" @click="openSkillsModal = false" class="text-gray-400 hover:text-gray-600 font-bold text-xl">&times;</button>
            </div>

            <form action="admin-staffservices.php" method="POST" class="flex flex-col flex-1 overflow-hidden">
                <input type="hidden" name="staff_id" :value="currentStaffId">

                <template x-for="sId in selectedServiceIds" :key="sId">
                    <input type="hidden" name="services[]" :value="sId">
                </template>

                <div class="p-5 overflow-y-auto space-y-3 flex-1 bg-amber-50/30">
                    <div class="text-sm text-gray-500 mb-2">เลือกประเภทบริการที่หมอนวดคนนี้สามารถให้บริการได้:</div>

                    <template x-for="group in groupedServices" :key="group.name">
                        <div class="border rounded-xl bg-white p-3 space-y-2 shadow-sm">
                            <label class="flex items-center justify-between cursor-pointer">
                                <div class="flex items-center gap-3">
                                    <input
                                        type="checkbox"
                                        :checked="isGroupSelected(group)"
                                        @change="toggleGroup(group, $event.target.checked)"
                                        class="w-4 h-4 text-[#382214] rounded focus:ring-[#382214]"
                                    >
                                    <div>
                                        <div class="font-bold text-base text-[#382214]" x-text="group.name"></div>
                                    </div>
                                </div>
                                <span class="text-sm text-amber-800 font-semibold bg-amber-100 px-2 py-0.5 rounded-full" x-text="group.services.length + ' รายการ'"></span>
                            </label>

                            <div class="pl-7 pt-1 border-t border-gray-100 flex flex-wrap gap-1.5">
                                <template x-for="srv in group.services" :key="srv.id">
                                    <span class="text-[16px] bg-gray-100 text-gray-600 px-2 py-0.5 rounded" x-text="srv.name"></span>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="p-4 border-t bg-white flex justify-end space-x-3">
                    <button type="button" @click="openSkillsModal = false" class="px-4 py-2 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-100">
                        ยกเลิก
                    </button>
                    <button type="submit" class="px-6 py-2 rounded-xl text-sm font-bold bg-[#047857] hover:bg-[#065f46] text-white shadow-md active:scale-95">
                        บันทึก
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL POPUP เพิ่มพนักงานใหม่ -->
    <div id="addStaffModal" class="modal-backdrop fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="modal-content bg-[#FDFBF7] rounded-2xl p-6 w-full max-w-md shadow-2xl border border-[#E8E2D5] max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center pb-3 border-b border-[#E8E2D5] mb-4">
                <h3 class="text-xl font-bold text-[#382214]">เพิ่มพนักงานใหม่</h3>
                <button onclick="closeModal('addStaffModal')" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
            </div>
                
            <form action="addstaff.php" method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-[#382214] mb-1">ชื่อ-นามสกุล <span class="text-red-500">*</span></label>
                    <input
                        type="text"
                        name="name"
                        required
                        class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#047857]"
                    >
                </div>

                <div>
                    <label class="block text-sm font-semibold text-[#382214] mb-1">ชื่อเล่น</label>
                    <input
                        type="text"
                        name="nickname"
                        class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#047857]"
                    >
                </div>

                <!-- เพิ่ม Username & Password -->
                <div class="p-3 bg-amber-50/50 rounded-xl border border-amber-200/60 space-y-3">
                    <p class="text-xs font-bold text-[#382214]">ข้อมูลสำหรับการเข้าสู่ระบบ (สำหรับหมอนวด)</p>
                    <div>
                        <label class="block text-sm font-semibold text-[#382214] mb-1">Username <span class="text-red-500">*</span></label>
                        <input
                            type="text"
                            name="username"
                            required
                            placeholder="ระบุ Username เข้าใช้งาน"
                            class="w-full border border-gray-300 rounded-xl p-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#047857]"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-[#382214] mb-1">Password <span class="text-red-500">*</span></label>
                        <input
                            type="password"
                            name="password"
                            required
                            placeholder="ระบุ รหัสผ่าน"
                            class="w-full border border-gray-300 rounded-xl p-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#047857]"
                        >
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-[#382214] mb-1">เบอร์โทรศัพท์ <span class="text-red-500">*</span></label>
                    <input
                        type="tel"
                        name="phone"
                        required
                        class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#047857]"
                    >
                </div>
        
                <div>
                    <label class="block text-sm font-semibold text-[#382214] mb-1">เพศ</label>
                    <select name="gender" class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#047857]">
                        <option value="male">ชาย</option>
                        <option value="female">หญิง</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-[#382214] mb-1">วันเกิด</label>
                    <input
                        type="text"
                        id="birthdate_picker"
                        name="birthdate"
                        placeholder="เลือกวัน/เดือน/ปีเกิด"
                        readonly
                        class="w-full border border-gray-300 rounded-xl p-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#047857] cursor-pointer"
                    >
                </div>

                <div>
                    <label class="block text-sm font-semibold text-[#382214] mb-1">วันหยุดประจำสัปดาห์</label>
                    <select name="day_off" class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#047857]">
                        <option value="Monday">วันจันทร์</option>
                        <option value="Tuesday">วันอังคาร</option>
                        <option value="Wednesday">วันพุธ</option>
                        <option value="Thursday">วันพฤหัสบดี</option>
                        <option value="Friday">วันศุกร์</option>
                        <option value="Saturday">วันเสาร์</option>
                        <option value="Sunday">วันอาทิตย์</option>
                    </select>
                </div>

                <!-- ปุ่มแอ็กชัน -->
                <div class="flex justify-end gap-3 pt-4">
                    <button
                        type="button"
                        onclick="closeModal('addStaffModal')"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl text-sm font-medium hover:bg-gray-300 transition-colors"
                    >
                        ยกเลิก
                    </button>
                    <button
                        type="submit"
                        class="px-5 py-2 bg-[#047857] text-white rounded-xl text-sm font-medium hover:bg-[#244735] transition-colors"
                    >
                        บันทึกข้อมูล
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL POPUP แก้ไขพนักงาน -->
    <div id="editStaffModal" class="modal-backdrop fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="modal-content bg-[#FDFBF7] rounded-2xl p-6 w-full max-w-md shadow-2xl border border-[#E8E2D5] max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center pb-3 border-b border-[#E8E2D5] mb-4">
                <h3 class="text-xl font-bold text-[#382214]">แก้ไขข้อมูลพนักงาน</h3>
                <button onclick="closeModal('editStaffModal')" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
            </div>

            <form action="editstaff.php" method="POST" class="space-y-4">
                <input type="hidden" id="edit_staff_form_staff_id" name="staff_id">

                <div>
                    <label class="block text-sm font-semibold text-[#382214] mb-1">ชื่อ-นามสกุล <span class="text-red-500">*</span></label>
                    <input
                        type="text"
                        id="edit_staff_name"
                        name="name"
                        required
                        class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#047857]"
                    >
                </div>

                <div>
                    <label class="block text-sm font-semibold text-[#382214] mb-1">ชื่อเล่น</label>
                    <input
                        type="text"
                        id="edit_nickname"
                        name="nickname"
                        class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#047857]"
                    >
                </div>

                <!-- แก้ไข Username & Password -->
                <div class="p-3 bg-amber-50/50 rounded-xl border border-amber-200/60 space-y-3">
                    <p class="text-xs font-bold text-[#382214]">ข้อมูลสำหรับการเข้าสู่ระบบ</p>
                    <div>
                        <label class="block text-sm font-semibold text-[#382214] mb-1">Username <span class="text-red-500">*</span></label>
                        <input
                            type="text"
                            id="edit_username"
                            name="username"
                            required
                            class="w-full border border-gray-300 rounded-xl p-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#047857]"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-[#382214] mb-1">Password เปลี่ยนใหม่</label>
                        <input
                            type="password"
                            id="edit_password"
                            name="password"
                            placeholder="เว้นว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน"
                            class="w-full border border-gray-300 rounded-xl p-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#047857]"
                        >
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-[#382214] mb-1">เบอร์โทรศัพท์ <span class="text-red-500">*</span></label>
                    <input
                        type="tel"
                        id="edit_phone"
                        name="phone"
                        required
                        class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#047857]"
                    >
                </div>

                <div>
                    <label class="block text-sm font-semibold text-[#382214] mb-1">เพศ</label>
                    <select id="edit_gender" name="gender" class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#047857]">
                        <option value="male">ชาย</option>
                        <option value="female">หญิง</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-[#382214] mb-1">วันเกิด</label>
                    <input
                        type="text"
                        id="edit_birthdate_picker"
                        name="birthdate"
                        placeholder="เลือกวัน/เดือน/ปีเกิด"
                        readonly
                        class="w-full border border-gray-300 rounded-xl p-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#047857] cursor-pointer"
                    >
                </div>

                <div>
                    <label class="block text-sm font-semibold text-[#382214] mb-1">วันหยุดประจำสัปดาห์</label>
                    <select id="edit_day_off" name="day_off" class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#047857]">
                        <option value="Monday">วันจันทร์</option>
                        <option value="Tuesday">วันอังคาร</option>
                        <option value="Wednesday">วันพุธ</option>
                        <option value="Thursday">วันพฤหัสบดี</option>
                        <option value="Friday">วันศุกร์</option>
                        <option value="Saturday">วันเสาร์</option>
                        <option value="Sunday">วันอาทิตย์</option>
                    </select>
                </div>

                <!-- ปุ่มแอ็กชัน -->
                <div class="flex justify-end gap-3 pt-4">
                    <button
                        type="button"
                        onclick="closeModal('editStaffModal')"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl text-sm font-medium hover:bg-gray-300 transition-colors"
                    >
                        ยกเลิก
                    </button>
                    <button
                        type="submit"
                        class="px-5 py-2 bg-[#047857] text-white rounded-xl text-sm font-medium hover:bg-[#244735] transition-colors"
                    >
                        บันทึกการแก้ไข
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL POPUP ลบพนักงาน -->
    <div id="deleteStaffModal" class="modal-backdrop fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="modal-content bg-[#FDFBF7] rounded-2xl p-6 w-full max-w-sm shadow-2xl border border-[#E8E2D5] text-center">
            <div class="w-12 h-12 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>

            <h3 class="text-xl font-bold text-[#382214] mb-2">ยืนยันการลบข้อมูล?</h3>
            <p class="text-sm text-gray-600 mb-6">คุณกำลังจะลบพนักงานชื่อ <span id="delete_staff_name" class="font-bold text-red-600"></span> ข้อมูลนี้เมื่อลบแล้วจะไม่สามารถฟื้นคืนได้</p>

            <form action="deletestaff.php" method="POST" class="flex justify-center gap-3">
                <input type="hidden" id="delete_staff_id" name="staff_id">
                <button
                    type="button"
                    onclick="closeModal('deleteStaffModal')"
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl text-sm font-medium hover:bg-gray-300 transition-colors"
                >
                    ยกเลิก
                </button>
                <button
                    type="submit"
                    class="px-5 py-2 bg-red-600 text-white rounded-xl text-sm font-medium hover:bg-red-700 transition-colors"
                >
                    ยืนยัน
                </button>
            </form>
        </div>
    </div>
</section>

<script>
    function openEditModal(btn) {
        const staffId   = btn.getAttribute('data-id');
        const name      = btn.getAttribute('data-name');
        const nickname  = btn.getAttribute('data-nickname');
        const username  = btn.getAttribute('data-username');
        const phone     = btn.getAttribute('data-phone');
        const gender    = btn.getAttribute('data-gender');
        const birthdate = btn.getAttribute('data-birthdate');
        const dayOff    = btn.getAttribute('data-dayoff');

        document.getElementById('edit_staff_form_staff_id').value = staffId;
        document.getElementById('edit_staff_name').value = name;
        document.getElementById('edit_nickname').value = nickname;
        document.getElementById('edit_username').value = username;
        document.getElementById('edit_password').value = ''; // เคลียร์รหัสผ่านเก่าเสมอ
        document.getElementById('edit_phone').value = phone;
        document.getElementById('edit_gender').value = gender;
        document.getElementById('edit_day_off').value = dayOff;

        const birthInput = document.getElementById('edit_birthdate_picker');
        if (birthInput) birthInput.value = birthdate;

        try {
            if (typeof editDatePicker !== 'undefined' && editDatePicker) {
                if (birthdate) {
                    editDatePicker.setDate(birthdate);
                } else {
                    editDatePicker.clear();
                }
            }
        } catch (err) {
            console.warn('DatePicker notice:', err);
        }

        openModal('editStaffModal');
    }

    function openDeleteModal(staffId, staffName) {
        document.getElementById('delete_staff_id').value = staffId;
        document.getElementById('delete_staff_name').textContent = staffName;
        openModal('deleteStaffModal');
    }
</script>