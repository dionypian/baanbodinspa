<?php
session_start();

// เช็คสิทธิ์การเข้าถึง (เฉพาะแอดมินเท่านั้น)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once 'db-connect.php';
require_once 'auto_assign.php';

// ดึงข้อมูลทักษะบริการของพนักงานทั้งหมด (staff_id => [service_ids])
$stmt_staff_skills = $pdo->query("SELECT staff_id, service_id FROM staff_services");
$staff_skills_raw = $stmt_staff_skills->fetchAll(PDO::FETCH_ASSOC);

$staff_skills_map = [];
foreach ($staff_skills_raw as $row) {
    $staff_skills_map[$row['staff_id']][] = (int)$row['service_id'];
}

// ดึงข้อมูลทักษะบริการของพนักงานทั้งหมด (staff_id => [service_ids])
$stmt_staff_skills = $pdo->query("SELECT staff_id, service_id FROM staff_services");
$staff_skills_raw = $stmt_staff_skills->fetchAll(PDO::FETCH_ASSOC);

$staff_skills_map = [];
foreach ($staff_skills_raw as $row) {
    $staff_skills_map[$row['staff_id']][] = (int)$row['service_id'];
}

date_default_timezone_set('Asia/Bangkok');
$today = date('Y-m-d');

// รับค่า Step ปัจจุบัน
$step = isset($_POST['step']) ? (int)$_POST['step'] : 1;

// จัดการการส่งค่า POST ระหว่าง Step
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['step_from']) && $_POST['step_from'] == 1) {
        $_SESSION['admin_booking']['cus_name']       = trim($_POST['cus_name'] ?? '');
        $_SESSION['admin_booking']['cus_phone']      = trim($_POST['cus_phone'] ?? '');
        $_SESSION['admin_booking']['customer_count'] = (int)($_POST['customer_count'] ?? 1);
        $_SESSION['admin_booking']['booking_date']   = $_POST['booking_date'] ?? $today;
        $_SESSION['admin_booking']['booking_time']   = $_POST['booking_time'] ?? '10:00';
    }

    if (isset($_POST['step_from']) && $_POST['step_from'] == 2) {
        if (isset($_POST['items'])) {
            $_SESSION['admin_booking']['items'] = $_POST['items'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มรายการจองคิว - Baanbodinspa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Flatpickr CSS & JS (เหมือนฝั่งลูกค้า) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/th.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/buddhistEra.js"></script>

    <style> 
        body { font-family: 'Sarabun', sans-serif; } 
        [x-cloak] { display: none !important; }
    </style>
</head>

<body class="bg-[#FAF6F0] min-h-screen flex items-center justify-center p-4 sm:p-6">

    <div class="fixed inset-0 pointer-events-none opacity-20 z-0 overflow-hidden">
        <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="prajamyam-sparse-pattern" width="180" height="180" patternUnits="userSpaceOnUse">
                    <g fill="#382214">
                        <path d="M 90,90 C 78,80 77,65 90,55 C 103,65 102,80 90,90 Z" />
                        <path d="M 90,90 C 78,100 77,115 90,125 C 103,115 102,100 90,90 Z" />
                        <path d="M 90,90 C 80,78 65,77 55,90 C 65,103 80,102 90,90 Z" />
                        <path d="M 90,90 C 100,78 115,77 125,90 C 115,103 100,102 90,90 Z" />
                        <circle cx="90" cy="90" r="5" fill="#D2BFA6" />
                    </g>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#prajamyam-sparse-pattern)" />
        </svg>
    </div>

    <div class="relative z-10 bg-white rounded-3xl shadow-xl border border-[#E8E2D5] overflow-hidden w-full max-w-xl my-auto">
        
        <!-- Header -->
        <div class="bg-white text-[#E8E2D5] p-6 text-center border-b border-gray-200">
            <h1 class="text-2xl font-bold text-[#382214]">เพิ่มรายการจองคิว</h1>
            <p class="text-md text-[#382214] mt-1">โทรจอง / Walk-in</p>
        </div>

        <div class="p-6">

            <!-- STEP 1 -->
            <?php if ($step === 1): ?>
                <form action="admin-booking.php" method="POST" class="space-y-4">
                    <input type="hidden" name="step" value="2">
                    <input type="hidden" name="step_from" value="1">

                    <div class="bg-amber-50 p-4 space-y-3 rounded-2xl">
                        <h3 class="font-bold text-lg text-[#382214] text-center items-center gap-1.5 p-2">
                            ข้อมูลลูกค้า
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-[#382214] mb-1">ชื่อลูกค้า <span class="text-red-500">*</span></label>
                                <input type="text" name="cus_name" required
                                    value="<?= htmlspecialchars($_SESSION['admin_booking']['cus_name'] ?? ''); ?>"
                                    class="w-full rounded-xl border border-gray-300 p-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#382214]">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-[#382214] mb-1">เบอร์โทรศัพท์ <span class="text-red-500"></span></label>
                                <input type="tel" name="cus_phone"
                                    value="<?= htmlspecialchars($_SESSION['admin_booking']['cus_phone'] ?? ''); ?>"
                                    class="w-full rounded-xl border border-gray-300 p-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#382214]">
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-[#382214]">จำนวนผู้ใช้บริการ</label>
                        <select name="customer_count" required
                            class="w-full rounded-xl border border-gray-300 p-2.5 bg-white text-[#382214] focus:outline-none focus:ring-2 focus:ring-[#382214]">
                            <?php 
                            $selected_count = $_SESSION['admin_booking']['customer_count'] ?? 1;
                            for ($i = 1; $i <= 6; $i++): 
                            ?>
                                <option value="<?= $i ?>" <?= $selected_count == $i ? 'selected' : '' ?>>
                                    <?= $i ?> ท่าน
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <!-- วันที่จอง (ใช้ Flatpickr ปฏิทินแบบลูกค้า) -->
                    <div>
                        <label class="block text-sm font-medium text-[#382214] mb-1">วันที่จอง</label>
                        <div class="relative">
                            <input type="text" name="booking_date" id="booking_date" required readonly
                                value="<?= htmlspecialchars($_SESSION['admin_booking']['booking_date'] ?? $today); ?>"
                                class="w-full rounded-xl border border-gray-300 p-2.5 bg-white text-[#382214] cursor-pointer focus:outline-none focus:ring-2 focus:ring-[#382214]">
                            <span class="absolute right-3 top-3 text-gray-400 pointer-events-none">📅</span>
                        </div>
                    </div>

                    <!-- เวลาที่จอง (เพิ่มทีละ 10 นาที) -->
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-[#382214]">เวลาที่จอง</label>
                        <select name="booking_time" required 
                            class="w-full rounded-xl border border-gray-300 p-2.5 bg-white text-[#382214] focus:outline-none focus:ring-2 focus:ring-[#382214]">
                            <?php
                            $start = strtotime('10:00');
                            $end   = strtotime('22:00');
                            $selected_time = $_SESSION['admin_booking']['booking_time'] ?? '10:00';

                            // 600 วินาที = 10 นาที
                            for ($i = $start; $i <= $end; $i += 600) {
                                $time_str = date('H:i', $i);
                                $is_selected = ($selected_time === $time_str) ? 'selected' : '';
                                echo "<option value=\"{$time_str}\" {$is_selected}>{$time_str} น.</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="flex space-x-3 pt-3">
                        <a href="dashboard.php" 
                           class="w-1/2 text-center bg-gray-200 text-gray-700 py-3 rounded-xl hover:bg-gray-300 transition-colors text-sm font-medium flex items-center justify-center gap-1">
                            ยกเลิก / กลับ Dashboard
                        </a>
                        <button type="submit" class="w-1/2 bg-[#382214] text-white py-3 rounded-xl hover:bg-[#5A3A29] transition-colors text-sm font-medium shadow">
                            ถัดไป (เลือกบริการ)
                        </button>
                    </div>
                </form>
            <?php endif; ?>

            <!-- STEP 2 -->
            <?php if ($step === 2): 
                $customer_count = $_SESSION['admin_booking']['customer_count'] ?? 1;
                $booking_date   = $_SESSION['admin_booking']['booking_date'] ?? '';
                $booking_time   = $_SESSION['admin_booking']['booking_time'] ?? '';

                // 1. ดึงพนักงานทั้งหมดที่ไม่ได้หยุดประจำวัน (ไม่กรองเวลาที่นี่ เพื่อให้นำไปเช็คช่วงเวลาซ้อนทับใน JS)
                $sql = "SELECT s.staff_id, s.name, s.nickname 
                        FROM staffs s 
                        WHERE (s.day_off IS NULL OR DAYNAME(:booking_date) != s.day_off)";

                $stmt_staff = $pdo->prepare($sql);
                $stmt_staff->execute([':booking_date' => $booking_date]);
                $staffs = $stmt_staff->fetchAll(PDO::FETCH_ASSOC);

                // 🟢 2. ดึงคิวจองทั้งหมดในวันที่เลือก เพื่อคำนวณช่วงเวลาที่หมอนวดติดคิว (เวลาเริ่ม - เวลาจบ)
                $sql_busy = "SELECT b.staff_id, b.booking_time, SUM(srv.duration) as total_duration
                            FROM bookings b
                            LEFT JOIN booking_items bi ON b.id = bi.booking_id
                            LEFT JOIN allservices srv ON bi.service_id = srv.id
                            WHERE b.booking_date = :b_date 
                            AND b.status != 'cancelled'
                            AND b.staff_id IS NOT NULL
                            GROUP BY b.id";

                $stmt_busy = $pdo->prepare($sql_busy);
                $stmt_busy->execute([':b_date' => $booking_date]);
                $busy_raw = $stmt_busy->fetchAll(PDO::FETCH_ASSOC);

                // แปลงเวลาเริ่มต้นและระยะเวลาให้เป็นช่วงเวลาวินาที (Timestamp)
                $busy_slots = [];
                foreach ($busy_raw as $b) {
                    $start_ts = strtotime($booking_date . ' ' . $b['booking_time']);
                    $duration = !empty($b['total_duration']) ? (int)$b['total_duration'] : 60; // หากไม่มีบริการ คิดขั้นต่ำ 60 นาที
                    $end_ts   = $start_ts + ($duration * 60);

                    $busy_slots[] = [
                        'staff_id' => (int)$b['staff_id'],
                        'start'    => $start_ts,
                        'end'      => $end_ts
                    ];
                }

                // 3. จัดกลุ่มบริการตาม category และ group_name จากตาราง allservices
                $stmt_services = $pdo->query("SELECT * FROM allservices WHERE status = 'active' ORDER BY category ASC, group_name ASC, id ASC");
                $raw_services = $stmt_services->fetchAll(PDO::FETCH_ASSOC);

                $services_by_cat = [];
                foreach ($raw_services as $srv) {
                    $cat   = !empty($srv['category']) ? $srv['category'] : 'บริการทั่วไป';
                    $group = !empty($srv['group_name']) ? $srv['group_name'] : 'รายการบริการ';
                    $services_by_cat[$cat][$group][] = $srv;
                }

                $initial_selected_services = $_SESSION['admin_booking']['items_obj'] ?? [];
                
                $initial_selected_staffs = [];
                for ($i = 1; $i <= $customer_count; $i++) {
                    $initial_selected_staffs[$i] = $_SESSION['admin_booking']['items'][$i]['staff_id'] ?? '';
                }

                // คำนวณเวลาเริ่มต้นการจองของรอบนี้เป็น Timestamp วินาที
                $current_booking_start_ts = strtotime($booking_date . ' ' . $booking_time);
            ?>
                <div x-data="{ 
                    openModal: false, 
                    activePerson: 1, 
                    customerCount: <?= (int)$customer_count ?>,
                    selectedServices: <?= htmlspecialchars(json_encode($initial_selected_services ?? []), ENT_QUOTES, 'UTF-8') ?>,
                    selectedStaffs: <?= htmlspecialchars(json_encode($initial_selected_staffs ?? []), ENT_QUOTES, 'UTF-8') ?>,
                    busySlots: <?= htmlspecialchars(json_encode($busy_slots ?? []), ENT_QUOTES, 'UTF-8') ?>,
                    currentStartTs: <?= (int)($current_booking_start_ts ?? time()) ?>,
                    staffSkillsMap: <?= htmlspecialchars(json_encode($staff_skills_map ?? []), ENT_QUOTES, 'UTF-8') ?>,

                    // 1. เช็คว่าหมอถูกเลือกไปแล้วโดยคนอื่นในกลุ่มเดียวกันหรือไม่
                    isStaffSelectedByOther(staffId, currentPerson) {
                        if (!staffId) return false;
                        for (let p in this.selectedStaffs) {
                            if (p != currentPerson && String(this.selectedStaffs[p]) === String(staffId)) {
                                return true;
                            }
                        }
                        return false;
                    },

                    // 2. เช็คว่าหมอนวดคนนี้มีทักษะครบถ้วนตามบริการที่เลือกไว้หรือไม่
                    isStaffQualified(staffId, currentPerson) {
                        if (!staffId) return true;
                        
                        const personServices = this.selectedServices[currentPerson] || [];
                        if (personServices.length === 0) return true;

                        const staffSkills = (this.staffSkillsMap[staffId] || []).map(Number);

                        return personServices.every(srv => {
                            const serviceId = typeof srv === 'object' && srv !== null ? parseInt(srv.id) : parseInt(srv);
                            return staffSkills.includes(serviceId);
                        });
                    },

                    // 3. เช็คว่าหมอนวดติดคิวนวดช่วงเวลานี้หรือไม่ (Overlapping Check)
                    isStaffBusy(staffId, currentPerson) {
                        if (!staffId) return false;

                        const personServices = this.selectedServices[currentPerson] || [];
                        const totalDuration = personServices.reduce((sum, srv) => {
                            const dur = (typeof srv === 'object' && srv !== null) ? srv.duration : 60;
                            return sum + parseInt(dur || 60);
                        }, 0);
                        
                        const myStart = this.currentStartTs;
                        const myEnd   = myStart + ((totalDuration > 0 ? totalDuration : 60) * 60);

                        return (this.busySlots || []).some(slot => {
                            if (parseInt(slot.staff_id) === parseInt(staffId)) {
                                return (myStart < slot.end && myEnd > slot.start);
                            }
                            return false;
                        });
                    },

                    // 4. เช็คว่าทุกคนเลือกบริการครบหรือยัง
                    isAllServicesSelected() {
                        for (let i = 1; i <= this.customerCount; i++) {
                            if (!this.selectedServices[i] || this.selectedServices[i].length === 0) {
                                return false;
                            }
                        }
                        return true;
                    }
                }">
    
                    <form action="admin-booking.php" method="POST">
                        <input type="hidden" name="step" value="3">
                        <input type="hidden" name="step_from" value="2">

                        <?php for ($i = 1; $i <= $customer_count; $i++): ?>
                            <div class="bg-white p-4 rounded-2xl border border-gray-200 space-y-3 mb-4 shadow-sm">
                                <h3 class="font-bold text-[#382214] border-b pb-2 text-base">
                                    ลูกค้าคนที่ <?= $i ?>
                                </h3>

                                <!-- ส่วนเลือกบริการ -->
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">บริการที่เลือก (สูงสุด 3 รายการ) <span class="text-red-500">*</span></label>
                                    <button type="button" 
                                        @click="openModal = true; activePerson = <?= $i ?>; $dispatch('set-temp-list', selectedServices[<?= $i ?>] ? JSON.parse(JSON.stringify(selectedServices[<?= $i ?>])) : []);"
                                        class="w-full rounded-xl border border-gray-300 p-3 bg-gray-50 text-left flex justify-between items-center hover:border-[#382214] transition-colors">
                                        
                                        <template x-if="selectedServices[<?= $i ?>] && selectedServices[<?= $i ?>].length > 0">
                                            <div class="text-sm text-[#382214] font-medium space-y-1 w-full pr-2">
                                                <template x-for="s in selectedServices[<?= $i ?>]" :key="s.id">
                                                    <div class="flex justify-between items-center">
                                                        <span x-text="s.name"></span>
                                                        <span class="text-sm text-gray-500" x-text="'(' + s.duration + ' นาที - ' + parseInt(s.price).toLocaleString() + '฿)'"></span>
                                                    </div>
                                                </template>
                                            </div> 
                                        </template>

                                        <template x-if="!selectedServices[<?= $i ?>] || selectedServices[<?= $i ?>].length === 0">
                                            <span class="text-sm text-red-500">-- กรุณากดเลือกบริการอย่างน้อย 1 รายการ --</span>
                                        </template>

                                        <span class="text-xs bg-[#382214] text-white px-2.5 py-1 rounded-lg shrink-0 ml-2">เลือก</span>
                                    </button>

                                    <!-- Hidden input สำหรับส่ง service_ids ไปบันทึก -->
                                    <template x-for="(s, idx) in selectedServices[<?= $i ?>]" :key="s.id">
                                        <input type="hidden" :name="'items[<?= $i ?>][service_ids][' + idx + ']'" :value="s.id">
                                    </template>
                                </div>

                                <!-- ส่วนเลือกพนักงานนวด -->
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">เลือกพนักงานนวด (ไม่ระบุได้)</label>
                                    <select name="items[<?= $i ?>][staff_id]" 
                                            x-model="selectedStaffs[<?= $i ?>]"
                                            :disabled="!selectedServices[<?= $i ?>] || selectedServices[<?= $i ?>].length === 0"
                                            :class="(!selectedServices[<?= $i ?>] || selectedServices[<?= $i ?>].length === 0) 
                                                ? 'bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed opacity-60' 
                                                : 'bg-gray-50 text-[#382214] border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#382214] cursor-pointer'"
                                            class="w-full rounded-xl p-2.5 text-sm transition-all duration-200">
                                        
                                        <!-- ตัวเลือกกรณีปลดล็อก/ยังไม่ล็อก -->
                                        <template x-if="selectedServices[<?= $i ?>] && selectedServices[<?= $i ?>].length > 0">
                                            <option value="">-- ไม่ระบุพนักงาน --</option>
                                        </template>

                                        <!-- ตัวเลือกแจ้งเตือนกรณีที่ยังเลือกล็อกอยู่ -->
                                        <template x-if="!selectedServices[<?= $i ?>] || selectedServices[<?= $i ?>].length === 0">
                                            <option value="" selected>กรุณาเลือกบริการด้านบนก่อนเพื่อเลือกพนักงาน</option>
                                        </template>

                                        <?php foreach ($staffs as $staff): ?>
                                            <option value="<?= $staff['staff_id'] ?>"
                                                :disabled="isStaffSelectedByOther(<?= $staff['staff_id'] ?>, <?= $i ?>) || !isStaffQualified(<?= $staff['staff_id'] ?>, <?= $i ?>) || isStaffBusy(<?= $staff['staff_id'] ?>, <?= $i ?>)"
                                                :class="(isStaffSelectedByOther(<?= $staff['staff_id'] ?>, <?= $i ?>) || !isStaffQualified(<?= $staff['staff_id'] ?>, <?= $i ?>) || isStaffBusy(<?= $staff['staff_id'] ?>, <?= $i ?>)) ? 'text-gray-400 bg-gray-100' : ''"
                                                x-text="'หมอ <?= htmlspecialchars($staff['nickname'] ?? $staff['name'], ENT_QUOTES) ?>' + 
                                                        (isStaffSelectedByOther(<?= $staff['staff_id'] ?>, <?= $i ?>) ? ' (ถูกเลือกแล้วในกลุ่มนี้)' : 
                                                        (!isStaffQualified(<?= $staff['staff_id'] ?>, <?= $i ?>) ? ' (ไม่มีทักษะบริการนี้)' : 
                                                        (isStaffBusy(<?= $staff['staff_id'] ?>, <?= $i ?>) ? ' (ติดคิวนวดช่วงเวลานี้)' : '')))">
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        <?php endfor; ?>

                        <!-- MODAL POPUP SELECT SERVICE -->
                        <div x-show="openModal" 
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-200"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 sm:p-6"
                            style="display: none;">
                            
                            <div class="bg-[#FDFBF7] w-full max-w-2xl rounded-3xl shadow-2xl border border-[#E8E2D5] overflow-hidden flex flex-col h-[85vh]"
                                x-data="{ 
                                    activeTab: '<?= !empty($services_by_cat) ? array_keys($services_by_cat)[0] : '' ?>', 
                                    openAccordion: null, 
                                    tempSelectedList: [],
                                    
                                    get totalDuration() {
                                        return this.tempSelectedList.reduce((sum, item) => sum + parseInt(item.duration), 0);
                                    },
                                    get totalPrice() {
                                        return this.tempSelectedList.reduce((sum, item) => sum + parseInt(item.price), 0);
                                    },
                                    get isOverClosingTime() {
                                        const bookingTime = '<?= $_SESSION['admin_booking']['booking_time'] ?? '10:00' ?>';
                                        const [hours, minutes] = bookingTime.split(':').map(Number);
                                        
                                        const startMinutes = (hours * 60) + minutes;
                                        const endMinutes = startMinutes + this.totalDuration;
                                        const closeMinutes = 23 * 60;

                                        return endMinutes > closeMinutes;
                                    },
                                    toggleService(item) {
                                        const index = this.tempSelectedList.findIndex(s => s.id === item.id);
                                        if (index > -1) {
                                            this.tempSelectedList.splice(index, 1);
                                        } else {
                                            if (this.tempSelectedList.length >= 3) {
                                                alert('สามารถเลือกบริการได้สูงสุด 3 รายการต่อท่าน');
                                                return;
                                            }
                                            this.tempSelectedList.push(item);
                                        }
                                    }
                                }"
                                @set-temp-list.window="tempSelectedList = $event.detail"
                                @click.outside="openModal = false">
                                
                                <div class="p-5 flex justify-between items-center bg-white border-b border-gray-100">
                                    <div>
                                        <h3 class="font-bold text-[#382214] text-xl">เลือกบริการ (สูงสุด 3 รายการ)</h3>
                                        <p class="text-xs text-[#382214] mt-0.5">สำหรับลูกค้าคนที่ <span class="font-bold text-[#382214]" x-text="activePerson"></span></p>
                                    </div>
                                    <button type="button" @click="openModal = false" class="text-gray-400 hover:text-black text-2xl font-bold p-2 hover:bg-gray-100 rounded-full transition-colors">✕</button>
                                </div>

                                <div class="sticky top-0 z-10 flex px-6 pt-4 bg-white gap-3 overflow-x-auto shrink-0 shadow-sm border-b border-[#382214]">
                                    <?php foreach (array_keys($services_by_cat) as $catName): ?>
                                        <button type="button" 
                                            @click="activeTab = '<?= htmlspecialchars($catName, ENT_QUOTES) ?>'"
                                            :class="activeTab === '<?= htmlspecialchars($catName, ENT_QUOTES) ?>' ? 'bg-[#FAF6F0] text-[#382214] font-bold border-t-2 border-x-2 border-[#382214]' : 'bg-white text-gray-500 border border-gray-300 hover:bg-gray-50'"
                                            class="px-6 py-2.5 rounded-t-xl text-sm transition-all whitespace-nowrap">
                                            <?= htmlspecialchars($catName) ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>

                                <div class="p-6 overflow-y-auto space-y-4 flex-grow bg-[#FAF6F0]/50">
                                    <?php foreach ($services_by_cat as $catName => $groups): ?>
                                        <div x-show="activeTab === '<?= htmlspecialchars($catName, ENT_QUOTES) ?>'" class="space-y-4">
                                            <?php foreach ($groups as $groupName => $items): ?>
                                                <div class="border border-gray-200 rounded-2xl bg-white overflow-hidden shadow-sm">
                                                    <button type="button" 
                                                        @click="openAccordion = (openAccordion === '<?= htmlspecialchars($groupName, ENT_QUOTES) ?>' ? null : '<?= htmlspecialchars($groupName, ENT_QUOTES) ?>')"
                                                        class="w-full flex justify-between items-center p-4 text-left font-bold text-[#382214] text-sm bg-white hover:bg-amber-50/50 transition-colors">
                                                        <span><?= htmlspecialchars($groupName) ?></span>
                                                        <span x-text="openAccordion === '<?= htmlspecialchars($groupName, ENT_QUOTES) ?>' ? '▲' : '▼'" class="text-xs text-gray-400"></span>
                                                    </button>

                                                    <div x-show="openAccordion === '<?= htmlspecialchars($groupName, ENT_QUOTES) ?>'" x-collapse class="px-4 pb-4 space-y-2 border-t border-gray-100 bg-gray-50/50 pt-2">
                                                        <?php foreach ($items as $item): 
                                                            $duration = $item['duration'] ?? $item['duration_minutes'] ?? 0;
                                                        ?>
                                                            <label class="flex items-center space-x-3 text-[#382214] cursor-pointer hover:bg-amber-100/60 p-3 rounded-xl border border-gray-200 bg-white hover:border-amber-300 transition-colors">
                                                                <input type="checkbox" 
                                                                    :checked="tempSelectedList.some(s => s.id === '<?= $item['id'] ?>')"
                                                                    @change="toggleService({ id: '<?= $item['id'] ?>', name: '<?= htmlspecialchars($item['name'], ENT_QUOTES) ?>', duration: '<?= $duration ?>', price: '<?= $item['price'] ?>' })"
                                                                    class="w-5 h-5 text-[#382214] rounded focus:ring-[#382214]">
                                                                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center w-full pr-2">
                                                                    <span class="font-semibold text-sm"><?= htmlspecialchars($item['name']) ?></span>
                                                                    <span class="text-xs font-medium text-gray-600 mt-0.5 sm:mt-0">
                                                                        <?= $duration ?> นาที - <strong class="text-[#382214] text-sm"><?= number_format($item['price']) ?>฿</strong>
                                                                    </span>
                                                                </div>
                                                            </label>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="p-5 border-t border-gray-200 bg-white flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                                    <div class="text-sm">
                                        <div>เลือกแล้ว: <strong class="text-[#382214]" x-text="tempSelectedList.length"></strong>/3 รายการ</div>
                                        <div>เวลารวม: <strong class="text-[#382214]" x-text="totalDuration"></strong> นาที | ราคารวม: <strong class="text-[#047857]" x-text="totalPrice.toLocaleString()"></strong> บาท</div>
                                        <template x-if="isOverClosingTime">
                                            <p class="text-red-600 font-bold text-sm mt-1">⚠️ เวลานวดรวมเกินเวลาปิดร้าน (23:00 น.) กรุณาลดบริการลง</p>
                                        </template>
                                    </div>

                                    <div class="flex justify-end space-x-3">
                                        <button type="button" @click="openModal = false" class="px-4 py-2 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors">
                                            ยกเลิก
                                        </button>
                                        <button type="button" 
                                            :disabled="tempSelectedList.length === 0 || isOverClosingTime"
                                            :class="(tempSelectedList.length === 0 || isOverClosingTime) ? 'bg-gray-300 cursor-not-allowed text-gray-500' : 'bg-[#047857] text-white hover:bg-[#065f46] shadow-md active:scale-95'"
                                            @click="selectedServices[activePerson] = [...tempSelectedList]; openModal = false;"
                                            class="px-6 py-2 rounded-xl text-sm font-bold transition-all">
                                            ยืนยันการเลือก
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex space-x-3 mt-6">
                            <button type="submit" name="step" value="1" class="w-1/2 text-center bg-gray-200 text-gray-700 py-3 rounded-xl hover:bg-gray-300 transition-colors text-sm font-medium">
                                ย้อนกลับ
                            </button>
                            <button type="submit" 
                                :disabled="!isAllServicesSelected()"
                                :class="!isAllServicesSelected() ? 'bg-gray-300 text-gray-500 cursor-not-allowed' : 'bg-[#382214] text-white hover:bg-[#5A3A29] shadow active:scale-95'"
                                class="w-1/2 py-3 rounded-xl transition-all text-sm font-medium">
                                ถัดไป (ตรวจสอบรายการ)
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <?php if ($step === 3): 
                // รับค่าจาก $_POST หรือ $_SESSION
                if (isset($_POST['items'])) {
                    $_SESSION['admin_booking']['items'] = $_POST['items'];
                }
                
                $items = $_SESSION['admin_booking']['items'] ?? [];
                $total_price = 0;
                $summary_details = [];

                foreach ($items as $index => $item) {
                    $staff_id = !empty($item['staff_id']) ? $item['staff_id'] : null;
                    $service_ids = $item['service_ids'] ?? [];

                    $staff_name = 'ไม่ระบุ (ร้านจัดสรรให้)';
                    if ($staff_id) {
                        $stmt_st = $pdo->prepare("SELECT * FROM staffs WHERE staff_id = :id");
                        $stmt_st->execute([':id' => $staff_id]);
                        $staff = $stmt_st->fetch(PDO::FETCH_ASSOC);
                        if ($staff) {
                            $staff_name = 'หมอ ' . ($staff['nickname'] ?? $staff['name']);
                        }
                    }

                    $person_services = [];
                    $person_total_price = 0;
                    $person_total_duration = 0;

                    foreach ($service_ids as $s_id) {
                        $stmt_s = $pdo->prepare("SELECT * FROM allservices WHERE id = :id");
                        $stmt_s->execute([':id' => $s_id]);
                        $service = $stmt_s->fetch(PDO::FETCH_ASSOC);

                        if ($service) {
                            $person_services[] = $service;
                            $person_total_price += $service['price'];
                            $person_total_duration += $service['duration'];
                        }
                    }

                    $total_price += $person_total_price;
                    $summary_details[] = [
                        'person'         => $index,
                        'services'       => $person_services,
                        'total_duration' => $person_total_duration,
                        'total_price'    => $person_total_price,
                        'staff'          => $staff_name
                    ];
                }
            ?>
                <div class="space-y-4">
                    <div class="space-y-4">
                    <div class="bg-white p-4 rounded-2xl border border-gray-200 space-y-3 text-sm">
                        <h3 class="font-bold text-[#382214] text-center text-lg">สรุปรายการการจอง</h3>

                        <div class="space-y-3 mt-3">
                            <?php foreach ($summary_details as $detail): ?>
                                <div class="p-3 bg-gray-50 rounded-xl border border-gray-200 text-base space-y-2">
                                    <div class="font-bold text-[#382214] flex justify-between border-b pb-1">
                                        <span>ลูกค้าคนที่ <?= $detail['person'] ?></span>
                                        <span class="text-[#5A3A29]"><?= number_format($detail['total_price']) ?> บาท</span>
                                    </div>
                                    <div class="text-gray-600 space-y-1">
                                        <div class="font-semibold text-gray-700">บริการที่เลือก:</div>
                                        <?php foreach ($detail['services'] as $s): ?>
                                            <div class="flex justify-between">
                                                <span><?= htmlspecialchars($s['name']) ?></span>
                                                <span class="text-gray-500"><?= $s['duration'] ?> นาที (<?= number_format($s['price']) ?>฿)</span>
                                            </div>
                                        <?php endforeach; ?>
                                        <div class="text-sm text-amber-800 pt-1 font-medium">เวลานวดรวม: <?= $detail['total_duration'] ?> นาที</div>
                                    </div>
                                    <div class="text-gray-600 pt-1 border-t">พนักงาน: <span class="font-semibold text-[#382214]"><?= htmlspecialchars($detail['staff']) ?></span></div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="flex justify-between items-center text-base font-bold text-[#382214]">
                            <span>ราคารวมทั้งสิ้น:</span>
                            <span class="text-lg text-[#5A3A29]"><?= number_format($total_price) ?> บาท</span>
                        </div>
                    </div>
                    </div>

                    <!-- ฟอร์มส่งไป STEP 4 เพื่อ INSERT ลงตารางจริง -->
                    <div class="flex space-x-3 pt-3">
                        <form action="admin-booking.php" method="POST" class="w-1/2">
                            <input type="hidden" name="step" value="2">
                            <button type="submit" class="w-full bg-gray-200 text-gray-700 py-3 rounded-xl hover:bg-gray-300 transition-colors text-sm font-medium">
                                ย้อนกลับ
                            </button>
                        </form>

                        <form action="admin-booking.php" method="POST" class="w-1/2">
                            <input type="hidden" name="step" value="4">
                            <button type="submit" class="w-full bg-[#047857] text-white py-3 rounded-xl hover:bg-[#065f46] transition-colors text-sm font-bold shadow-md active:scale-95">
                                ยืนยันการจองคิว
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- STEP 4: บันทึกข้อมูลลงฐานข้อมูลจริง -->
            <?php if ($step === 4): 
                $items          = $_SESSION['admin_booking']['items'] ?? [];
                $cus_name       = $_SESSION['admin_booking']['cus_name'] ?? 'ลูกค้า Walk-in';
                $cus_phone      = $_SESSION['admin_booking']['cus_phone'] ?? '';
                $customer_count = $_SESSION['admin_booking']['customer_count'] ?? 1;
                $booking_date   = $_SESSION['admin_booking']['booking_date'] ?? date('Y-m-d');
                $booking_time   = $_SESSION['admin_booking']['booking_time'] ?? '10:00';

                try {
                    $pdo->beginTransaction();
                    $is_same_day = $booking_date === date('Y-m-d');
                    $planned_staff_ids = $is_same_day
                        ? planSameDayStaffAssignments($pdo, $booking_date, $booking_time, $items)
                        : [];

                    // 1. หาหรือสร้าง cus_id จากเบอร์โทรศัพท์ลูกค้า (ถ้าตารางลูกค้าชื่อ customers)
                    $cus_id = null;
                    if (!empty($cus_phone)) {
                        $stmt_check_cus = $pdo->prepare("SELECT cus_id FROM customers WHERE phone = :phone LIMIT 1");
                        $stmt_check_cus->execute([':phone' => $cus_phone]);
                        $existing_cus = $stmt_check_cus->fetch(PDO::FETCH_ASSOC);

                        if ($existing_cus) {
                            $cus_id = $existing_cus['cus_id'];
                        } else {
                            $stmt_add_cus = $pdo->prepare("INSERT INTO customers (name, phone, created_at) VALUES (:name, :phone, NOW())");
                            $stmt_add_cus->execute([
                                ':name'  => $cus_name,
                                ':phone' => $cus_phone
                            ]);
                            $cus_id = $pdo->lastInsertId();
                        }
                    }

                    // 2. บันทึกคิวจองลงตาราง bookings และ booking_items
                    foreach ($items as $index => $item) {
                        $staff_id    = $is_same_day
                            ? ($planned_staff_ids[$index] ?? null)
                            : (!empty($item['staff_id']) ? (int)$item['staff_id'] : null);
                        $service_ids = $item['service_ids'] ?? [];
                        $status = $staff_id ? 'confirmed' : 'pending';

                        // 🟢 ดึง service_id แรกสุดเพื่อส่งเข้าตาราง bookings ให้ผ่าน Foreign Key Constraint
                        $main_service_id = !empty($service_ids) ? (int)$service_ids[0] : null;

                        // INSERT ลงตาราง bookings พร้อมระบุ service_id
                        $stmt_b = $pdo->prepare("
                            INSERT INTO bookings (cus_id, staff_id, service_id, booking_date, booking_time, status, created_at)
                            VALUES (:cus_id, :staff_id, :service_id, :b_date, :b_time, :status, NOW())
                        ");
                        $stmt_b->execute([
                            ':cus_id'     => $cus_id,
                            ':staff_id'   => $staff_id,
                            ':service_id' => $main_service_id, // 🟢 ใส่เพื่อปลดล็อก Foreign Key
                            ':b_date'     => $booking_date,
                            ':b_time'     => $booking_time,
                            ':status'     => $status
                        ]);

                        $new_booking_id = $pdo->lastInsertId();

                        // 3. บันทึกบริการทั้งหมดลงตาราง booking_items (เพื่อรองรับการจองหลายบริการ)
                        if (!empty($service_ids) && is_array($service_ids)) {
                            $stmt_item = $pdo->prepare("
                                INSERT INTO booking_items (booking_id, service_id)
                                VALUES (:booking_id, :service_id)
                            ");

                            foreach ($service_ids as $srv_id) {
                                if (!empty($srv_id)) {
                                    $stmt_item->execute([
                                        ':booking_id' => $new_booking_id,
                                        ':service_id' => (int)$srv_id
                                    ]);
                                }
                            }
                        }
                    }

                    $pdo->commit();
                    unset($_SESSION['admin_booking']); // เคลียร์ค่า Session
            ?>
                    <!-- หน้าแจ้งเตือนบันทึกสำเร็จ -->
                    <div class="text-center py-8 space-y-4">
                        <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto text-3xl font-bold">
                            ✓
                        </div>
                        <h2 class="text-2xl font-bold text-[#382214]">บันทึกการจองเรียบร้อยแล้ว!</h2>
                        <p class="text-sm text-gray-600">ข้อมูลคิวจองถูกบันทึกลงระบบตารางคิวแล้วเรียบร้อย</p>

                        <div class="pt-4">
                            <a href="dashboard.php?tab=queue" class="inline-block bg-[#382214] text-white px-8 py-3 rounded-xl hover:bg-[#5A3A29] transition-colors text-sm font-medium shadow">
                                กลับหน้าตารางคิวจอง
                            </a>
                        </div>
                    </div>
            <?php 
                } catch (Exception $e) {
                    $pdo->rollBack();
                    echo '<div class="p-4 bg-red-100 text-red-700 rounded-xl text-center font-bold mb-4">';
                    echo 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . htmlspecialchars($e->getMessage());
                    echo '<br><a href="admin-booking.php" class="text-sm underline mt-2 inline-block">ลองใหม่อีกครั้ง</a>';
                    echo '</div>';
                }
            endif; 
            ?>

        </div>
    </div>

</body>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const buddhistPlugin = typeof flatpickrBuddhistEra === 'function' 
            ? new flatpickrBuddhistEra() 
            : (window.flatpickrBuddhistEra ? flatpickrBuddhistEra() : null);

        flatpickr("#booking_date", {
            locale: "th",
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "j F Y",
            minDate: "today",
            plugins: buddhistPlugin ? [buddhistPlugin] : []
        });
    });
</script>
</html>