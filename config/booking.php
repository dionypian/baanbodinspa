<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'db-connect.php';

// ดึงข้อมูลจับคู่ทักษะพนักงานทั้งหมด (staff_id => [service_ids])
$stmt_staff_skills = $pdo->query("SELECT staff_id, service_id FROM staff_services");
$staff_skills_raw = $stmt_staff_skills->fetchAll(PDO::FETCH_ASSOC);

$staff_skills_map = [];
foreach ($staff_skills_raw as $row) {
    $staff_skills_map[$row['staff_id']][] = (int)$row['service_id'];
}

date_default_timezone_set('Asia/Bangkok');
$today = date('Y-m-d');
$min_booking_time = date('H:i', strtotime('+1 hour'));
$opentime = '10:00';
$closetime = '22:00';
$error_message = '';
$step = isset($_POST['step']) ? (int)$_POST['step'] : 1;

// --- STEP 1 -> STEP 2 ---
if ($step === 2 && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_date'])) {
    $count         = isset($_POST['customer_count']) ? (int)$_POST['customer_count'] : 1;
    $selected_date = $_POST['booking_date'] ?? '';
    $selected_time = $_POST['booking_time'] ?? '';

    if ($count < 1) $count = 1;
    if ($count > 6) $count = 6;

    if ($selected_date < $today) {
        $error_message = 'ไม่สามารถเลือกวันที่ในอดีตได้';
        $step = 1;
    } elseif ($selected_time < $opentime || $selected_time > $closetime) {
        $error_message = 'กรุณาเลือกเวลาในช่วงทำการ (' . $opentime . ' - ' . $closetime . ' น.)';
        $step = 1;
    } elseif ($selected_date === $today && $selected_time < $min_booking_time) {
        $error_message = 'ต้องทำการจองล่วงหน้าอย่างน้อย 1 ชั่วโมง';
        $step = 1;
    } else {
        $_SESSION['booking']['customer_count'] = $count;
        $_SESSION['booking']['booking_date']   = $selected_date;
        $_SESSION['booking']['booking_time']   = $selected_time;
    }
}

if ($step === 2 && !isset($_POST['booking_date'])) {
    if (!isset($_SESSION['booking']['booking_date'])) {
        $step = 1;
    }
}

// --- STEP 2 -> STEP 3 ---
if ($step === 3 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $items = $_POST['items'] ?? [];
    $booking_time = $_SESSION['booking']['booking_time'] ?? '10:00';
    $start_timestamp = strtotime($booking_time);
    $close_timestamp = strtotime('23:00');

    if (!empty($items)) {
        $is_valid = true;
        $selected_staffs = [];

        foreach ($items as $person_idx => $item) {
            $staff_id    = !empty($item['staff_id']) ? $item['staff_id'] : null;
            $service_ids = $item['service_ids'] ?? [];

            if (empty($service_ids)) {
                $error_message = 'กรุณาเลือกบริการให้ครบถ้วน';
                $is_valid = false;
                break;
            }

            if (count($service_ids) > 3) {
                $error_message = 'สามารถเลือกบริการได้สูงสุด 3 รายการต่อท่านเท่านั้น';
                $is_valid = false;
                break;
            }

            $total_duration = 0;
            foreach ($service_ids as $s_id) {
                $stmt_dur = $pdo->prepare("SELECT duration FROM allservices WHERE id = :id");
                $stmt_dur->execute([':id' => $s_id]);
                $serv = $stmt_dur->fetch(PDO::FETCH_ASSOC);
                if ($serv) {
                    $total_duration += (int)$serv['duration'];
                }
            }

            $end_timestamp = $start_timestamp + ($total_duration * 60);
            if ($end_timestamp > $close_timestamp) {
                $error_message = "ลูกค้าคนที่ {$person_idx} มีเวลานวดรวมเกินเวลาปิดร้าน (23:00 น.)";
                $is_valid = false;
                break;
            }

            if ($staff_id !== null) {
                if (in_array($staff_id, $selected_staffs)) {
                    $error_message = 'ไม่สามารถเลือกพนักงานนวดซ้ำกันในเวลาเดียวกันได้';
                    $is_valid = false;
                    break;
                }
                $selected_staffs[] = $staff_id;
            }
        }

        if ($is_valid) {
            $_SESSION['booking']['items'] = $items;
        } else {
            $step = 2;
        }
    } else {
        $error_message = 'ไม่พบข้อมูลการเลือกบริการ';
        $step = 2;
    }
}

// ดึงรายการบริการทั้งหมดจัดกลุ่ม
$stmt = $pdo->query("SELECT * FROM allservices WHERE status = 'active' ORDER BY category ASC, group_name ASC, id ASC");
$all_services = $stmt->fetchAll(PDO::FETCH_ASSOC);

$services_by_cat = [];
foreach ($all_services as $s) {
    $cat = !empty($s['category']) ? $s['category'] : 'นวด';
    $group = !empty($s['group_name']) ? $s['group_name'] : 'รายการบริการ';
    $services_by_cat[$cat][$group][] = $s;
}

$user_id = $_SESSION['user_id'] ?? 0;
$stmt_user = $pdo->prepare("SELECT phone FROM customers WHERE cus_id = :id");
$stmt_user->execute([':id' => $user_id]);
$user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);
$user_phone = trim($user_data['phone'] ?? '');
$has_phone  = (!empty($user_phone) && strlen($user_phone) >= 9) ? 'true' : 'false';
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking - Baanbodinspa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/th.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body { font-family: 'Sarabun', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
</head>

<!-- 🟢 รวม x-data หลักไว้ที่ <body> เพื่อป้องกัน Scope ซ้อนกัน -->
<body class="bg-[#FAF6F0] text-gray-800 min-h-screen flex flex-col" 
      x-data="{ 
        openModal: false, 
        activePerson: 1, 
        selectedServices: {}, 
        selectedStaffs: {},
        staffSkillsMap: <?= htmlspecialchars(json_encode($staff_skills_map ?? []), ENT_QUOTES, 'UTF-8') ?>,
        showPhoneModal: false,
        userPhone: '<?= htmlspecialchars($user_phone ?? '', ENT_QUOTES) ?>',
        
        isStaffSelectedByOther(staffId, currentPerson) {
            if (!staffId) return false;
            for (let p in this.selectedStaffs) {
                if (p != currentPerson && this.selectedStaffs[p] == staffId) {
                    return true;
                }
            }
            return false;
        },

        isStaffQualified(staffId, currentPerson) {
            if (!staffId) return true;
            const personServices = this.selectedServices[currentPerson] || [];
            if (personServices.length === 0) return true;

            const staffSkills = (this.staffSkillsMap[staffId] || []).map(Number);
            return personServices.every(srv => {
                const serviceId = typeof srv === 'object' ? parseInt(srv.id) : parseInt(srv);
                return staffSkills.includes(serviceId);
            });
        },

        submitBooking() {
            if (!this.userPhone || this.userPhone.trim().length < 9) {
                this.showPhoneModal = true;
            } else {
                document.getElementById('saveBookingForm').submit();
            }
        }
      }">

    <?php include 'includes/nav3.php'; ?>

    <main class="flex-grow flex items-center justify-center p-4 relative z-10">
        <div class="w-full max-w-lg bg-[#FDFBF7]/90 backdrop-blur-md rounded-3xl shadow-2xl border border-[#E8E2D5] p-6 md:p-8 relative my-6 z-10">

            <div class="flex flex-col items-center justify-center text-center mb-6">
                <img src="image/logo1.png" alt="Baan Bodin Spa Logo" class="h-14 w-auto object-contain mx-auto mb-8">
                <h1 class="text-xl font-semibold text-[#382214]">ยินดีต้อนรับคุณ <?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?></h1>
                <p class="text-sm text-gray-600 mt-1">กรุณากรอกข้อมูลเพื่อทำการจองคิว</p>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-4 text-sm" role="alert">
                    <span class="block sm:inline"><?= htmlspecialchars($error_message) ?></span>
                </div>
            <?php endif; ?>

            <!-- STEP 1 -->
            <?php if ($step === 1): ?>
                <form action="booking.php" method="POST" class="space-y-4">
                    <div class="space-y-2">
                        <label for="customer_count" class="block text-sm font-medium text-[#382214]">จำนวนผู้ใช้บริการ</label>
                        <select name="customer_count" id="customer_count" required
                            class="w-full rounded-lg border border-gray-300 p-2.5 bg-white text-[#382214] cursor-pointer focus:outline-none focus:ring-2 focus:ring-[#382214]">
                            <?php 
                            $selected_count = $_SESSION['booking']['customer_count'] ?? 1;
                            for ($i = 1; $i <= 6; $i++): 
                            ?>
                                <option value="<?= $i ?>" <?= $selected_count == $i ? 'selected' : '' ?>>
                                    <?= $i ?> ท่าน
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-[#382214] mb-1">วันที่จอง</label>
                        <div class="relative">
                            <input type="text" name="booking_date" id="booking_date" required readonly
                                value="<?= htmlspecialchars($_SESSION['booking']['booking_date'] ?? $today); ?>"
                                class="w-full rounded-lg border border-gray-300 p-2.5 bg-white text-[#382214] cursor-pointer focus:outline-none focus:ring-2 focus:ring-[#382214]">
                            <span class="absolute right-3 top-3 text-gray-400 pointer-events-none">📅</span>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label for="booking_time" class="block text-sm font-medium text-[#382214]">เวลาที่จอง</label>
                        <select name="booking_time" id="booking_time" required 
                            class="w-full rounded-lg border border-gray-300 p-2.5 bg-white text-[#382214] cursor-pointer focus:outline-none focus:ring-2 focus:ring-[#382214]">
                            <?php
                            $start = strtotime('10:00');
                            $end   = strtotime('22:00');
                            $selected_time = $_SESSION['booking']['booking_time'] ?? '10:00';

                            for ($i = $start; $i <= $end; $i += 600) {
                                $time_str = date('H:i', $i);
                                $is_selected = ($selected_time === $time_str) ? 'selected' : '';
                                echo "<option value=\"{$time_str}\" {$is_selected}>{$time_str}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <input type="hidden" name="step" value="2">
                    <button type="submit"
                        class="w-full bg-[#382214] text-white py-2.5 px-4 rounded-lg hover:bg-[#5A3A29] transition-colors duration-300 font-medium mt-2">
                        ถัดไป
                    </button>
                </form>
            <?php endif; ?>

            <!-- STEP 2 -->
            <?php if ($step === 2): 
                $customer_count = $_SESSION['booking']['customer_count'] ?? 1;
                $booking_date   = $_SESSION['booking']['booking_date'] ?? '';
                $booking_time   = $_SESSION['booking']['booking_time'] ?? '';

                // 🟢 Query ดึงพนักงาน โดยเช็คช่วงเวลาซ้อนทับ (Time Overlap) ร่วมกับวันหยุด
                $sql = "SELECT s.staff_id, s.name, s.nickname 
                        FROM staffs s 
                        WHERE (s.day_off IS NULL OR LOWER(DAYNAME(:booking_date)) != LOWER(s.day_off))
                        AND s.staff_id NOT IN (
                            SELECT b.staff_id 
                            FROM bookings b
                            LEFT JOIN (
                                SELECT booking_id, SUM(srv.duration) as total_dur 
                                FROM booking_items bi 
                                JOIN allservices srv ON bi.service_id = srv.id 
                                GROUP BY booking_id
                            ) service_dur ON b.id = service_dur.booking_id
                            WHERE b.booking_date = :booking_date_check 
                            AND b.status != 'cancelled'
                            AND b.staff_id IS NOT NULL
                            AND (
                                -- เช็คว่าเวลาจองใหม่ (:new_booking_time) อยู่ในช่วงเวลาทำงานของคิวเดิมหรือไม่
                                :new_booking_time < ADDTIME(b.booking_time, SEC_TO_TIME(COALESCE(service_dur.total_dur, 60) * 60))
                                AND ADDTIME(:new_booking_time2, SEC_TO_TIME(60 * 60)) > b.booking_time
                            )
                        )";

                $stmt_staff = $pdo->prepare($sql);
                $stmt_staff->execute([
                    ':booking_date'       => $booking_date,
                    ':booking_date_check' => $booking_date,
                    ':new_booking_time'   => $booking_time,
                    ':new_booking_time2'  => $booking_time
                ]);
                $staffs = $stmt_staff->fetchAll(PDO::FETCH_ASSOC);
            ?>

                <form action="booking.php" method="POST">
                    <input type="hidden" name="step" value="3">

                    <?php for ($i = 1; $i <= $customer_count; $i++): ?>
                        <div class="bg-white p-4 rounded-2xl border border-gray-200 space-y-3 mb-4">
                            <h3 class="font-bold text-[#382214] border-b pb-2 text-base">
                                ลูกค้าคนที่ <?= $i ?>
                            </h3>

                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">บริการที่เลือก (สูงสุด 3 รายการ)</label>
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

                                <template x-for="(s, idx) in selectedServices[<?= $i ?>]" :key="s.id">
                                    <input type="hidden" :name="'items[<?= $i ?>][service_ids][' + idx + ']'" :value="s.id">
                                </template>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">เลือกพนักงานนวด (ไม่ระบุได้)</label>
                                <select name="items[<?= $i ?>][staff_id]" 
                                            x-model="selectedStaffs[<?= $i ?>]"
                                            :disabled="!selectedServices[<?= $i ?>] || selectedServices[<?= $i ?>].length === 0"
                                            :class="(!selectedServices[<?= $i ?>] || selectedServices[<?= $i ?>].length === 0) 
                                                ? 'bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed opacity-60' 
                                                : 'bg-gray-50 text-[#382214] border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#382214] cursor-pointer'"
                                            class="w-full rounded-xl p-2.5 text-sm transition-all duration-200">
                                    
                                    <template x-if="selectedServices[<?= $i ?>] && selectedServices[<?= $i ?>].length > 0">
                                        <option value="">-- ไม่ระบุพนักงาน --</option>
                                    </template>

                                    <template x-if="!selectedServices[<?= $i ?>] || selectedServices[<?= $i ?>].length === 0">
                                        <option value="" selected>กรุณาเลือกบริการด้านบนก่อนเพื่อเลือกพนักงาน</option>
                                    </template>

                                    <?php foreach ($staffs as $staff): ?>
                                        <option value="<?= $staff['staff_id'] ?>"
                                                :disabled="isStaffSelectedByOther(<?= $staff['staff_id'] ?>, <?= $i ?>) || !isStaffQualified(<?= $staff['staff_id'] ?>, <?= $i ?>)"
                                                :class="(isStaffSelectedByOther(<?= $staff['staff_id'] ?>, <?= $i ?>) || !isStaffQualified(<?= $staff['staff_id'] ?>, <?= $i ?>)) ? 'text-gray-400 bg-gray-100' : ''"
                                                x-text="'หมอ <?= htmlspecialchars($staff['nickname'] ?? $staff['name'], ENT_QUOTES) ?>' + (isStaffSelectedByOther(<?= $staff['staff_id'] ?>, <?= $i ?>) ? ' (ถูกเลือกแล้ว)' : (!isStaffQualified(<?= $staff['staff_id'] ?>, <?= $i ?>) ? ' (ไม่มีทักษะบริการนี้)' : ''))">
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    <?php endfor; ?>

                    <div class="flex space-x-3 mt-4">
                        <a href="booking.php" class="w-1/2 text-center bg-gray-200 text-gray-700 py-2.5 rounded-lg hover:bg-gray-300 transition-colors text-sm font-medium">
                            ย้อนกลับ
                        </a>
                        <button type="submit" class="w-1/2 bg-[#382214] text-white py-2.5 rounded-lg hover:bg-[#5A3A29] transition-colors text-sm font-medium">
                            ถัดไป
                        </button>
                    </div>
                </form>
            <?php endif; ?>

            <!-- STEP 3 -->
            <?php if ($step === 3): 
                $booking_date   = $_SESSION['booking']['booking_date'] ?? '';
                $booking_time   = $_SESSION['booking']['booking_time'] ?? '';
                $customer_count = $_SESSION['booking']['customer_count'] ?? 1;
                $items          = $_SESSION['booking']['items'] ?? [];

                $total_price = 0;
                $summary_details = [];

                foreach ($items as $index => $item) {
                    $staff_id = !empty($item['staff_id']) ? $item['staff_id'] : null;
                    $service_ids = $item['service_ids'] ?? [];

                    $staff_name = 'ไม่ระบุ (ให้ทางร้านจัดให้)';
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
                    <!-- ตาราง/การ์ดสรุปรายการการจอง -->
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

                    <!-- 🟢 ฟอร์มซ่อนสำหรับยิงข้อมูลไปบันทึก -->
                    <form id="saveBookingForm" action="savebooking.php" method="POST">
                        <input type="hidden" name="cus_phone" :value="userPhone">
                    </form>

                    <!-- 🟢 ปุ่มย้อนกลับและปุ่มยืนยัน -->
                    <div class="flex space-x-3">
                        <form action="booking.php" method="POST" class="w-1/2">
                            <input type="hidden" name="step" value="2">
                            <button type="submit" class="w-full bg-gray-200 text-gray-700 py-2.5 rounded-lg hover:bg-gray-300 transition-colors text-sm font-medium">
                                ย้อนกลับ
                            </button>
                        </form>

                        <div class="w-1/2">
                            <button type="button" 
                                    @click="submitBooking()" 
                                    class="w-full bg-[#382214] text-white py-2.5 rounded-lg hover:bg-[#5A3A29] transition-colors text-sm font-medium shadow cursor-pointer">
                                ยืนยันการจอง
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <!-- 🟢 MODAL POPUP SELECT SERVICE -->
    <div x-show="openModal" 
        x-cloak
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
                    const bookingTime = '<?= $_SESSION['booking']['booking_time'] ?? '10:00' ?>';
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
                            alert('สามารถเลือกบริการได้สูงสุด 3 รายการต่อท่านครับ');
                            return;
                        }
                        this.tempSelectedList.push(item);
                    }
                }
            }"
            @set-temp-list.window="tempSelectedList = $event.detail"
            @click.outside="openModal = false">
            
            <div class="p-5 flex justify-between items-center bg-white border-b">
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

    <div x-show="showPhoneModal" 
         x-cloak 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60 backdrop-blur-md p-4"
         style="display: none;">
        
        <div class="bg-[#FDFBF7] w-full max-w-md rounded-3xl p-6 shadow-2xl border border-[#E8E2D5] space-y-4 relative z-10"
             @click.outside="showPhoneModal = false">
            
            <div class="text-center space-y-2">
                <div class="w-14 h-14 bg-amber-100 text-amber-900 rounded-full flex items-center justify-center mx-auto text-2xl font-bold shadow-inner">
                    📞
                </div>
                <h3 class="text-xl font-bold text-[#382214]">กรุณากรอกเบอร์โทรศัพท์</h3>
                <p class="text-xs text-gray-600 leading-relaxed">เพื่อใช้สำหรับการติดต่อยืนยันคิวและการแจ้งเตือนการบริการ</p>
            </div>

            <div class="bg-amber-50/50 p-4 rounded-2xl space-y-1.5">
                <label class="block text-sm font-semibold text-[#382214] mb-1">เบอร์โทรศัพท์ติดต่อ <span class="text-red-500">*</span></label>
                <input type="tel" 
                       x-model="userPhone" 
                       placeholder="08X-XXX-XXXX"
                       maxlength="10"
                       class="w-full rounded-xl border border-gray-300 p-3 text-base text-center bg-white font-bold text-[#382214] tracking-widest focus:outline-none focus:ring-2 focus:ring-[#382214] focus:border-transparent shadow-sm">
            </div>

            <div class="flex space-x-3 pt-2">
                <button type="button" 
                        @click="showPhoneModal = false" 
                        class="w-1/2 bg-gray-200 text-gray-700 py-3 rounded-xl hover:bg-gray-300 text-sm font-medium transition-colors">
                    ยกเลิก
                </button>
                <button type="button" 
                        @click="if (userPhone && userPhone.trim().length >= 9) { showPhoneModal = false; $nextTick(() => document.getElementById('saveBookingForm').submit()); } else { alert('กรุณากรอกเบอร์โทรศัพท์ให้ถูกต้อง (9-10 หลัก)'); }" 
                        class="w-1/2 bg-[#382214] text-white py-3 rounded-xl hover:bg-[#5A3A29] text-sm font-medium transition-colors shadow">
                    บันทึกและยืนยัน
                </button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            flatpickr("#booking_date", {
                locale: "th",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "j F Y",
                minDate: "today",
                defaultDate: "<?= htmlspecialchars($_SESSION['booking']['booking_date'] ?? $today); ?>"
            });

            const staffSelects = document.querySelectorAll('select[name^="items"][name$="[staff_id]"]');

            staffSelects.forEach(select => {
                select.addEventListener('change', function () {
                    const currentSelect = this;
                    const selectedValue = currentSelect.value;

                    if (!selectedValue) return;

                    staffSelects.forEach(otherSelect => {
                        if (otherSelect !== currentSelect && otherSelect.value === selectedValue) {
                            alert('พนักงานท่านนี้ถูกเลือกไปแล้วสำหรับลูกค้าท่านอื่น กรุณาเลือกพนักงานท่านใหม่ครับ');
                            currentSelect.value = '';
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>