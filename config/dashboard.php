<?php
// เปิด Error Reporting เพื่อช่วยในการ Debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db-connect.php';
date_default_timezone_set('Asia/Bangkok');

$active_tab = $_GET['tab'] ?? 'summary';

// --- ดึงข้อมูลพนักงานทั้งหมด ---
$stmt_staffs = $pdo->query("SELECT * FROM staffs ORDER BY staff_id ASC");
$staffs = $stmt_staffs->fetchAll(PDO::FETCH_ASSOC);

// --- ดึงรายการบริการทั้งหมด ---
$stmt_all_srv = $pdo->query("SELECT id, name, category, group_name, duration, price FROM allservices WHERE status = 'active' ORDER BY category ASC, group_name ASC, id ASC");
$all_services = $stmt_all_srv->fetchAll(PDO::FETCH_ASSOC);
$all_services_list = $all_services;

// --- ดึงข้อมูลจับคู่ทักษะหมอนวด ---
$stmt_staff_skills = $pdo->query("SELECT staff_id, service_id FROM staff_services");
$staff_skills_raw = $stmt_staff_skills->fetchAll(PDO::FETCH_ASSOC);

$staff_skills_map = [];
foreach ($staff_skills_raw as $row) {
    $staff_skills_map[$row['staff_id']][] = (int)$row['service_id'];
}

$selected_date = !empty($_GET['filter_date']) ? $_GET['filter_date'] : date('Y-m-d');
$search_query  = trim($_GET['search_query'] ?? '');

$where_clauses = [];
$params = [];

// เงื่อนไขวันที่ (ถ้าไม่ได้เลือก "ดูคิวทั้งหมด")
if ($selected_date !== 'all') {
    $where_clauses[] = "b.booking_date = :selected_date";
    $params[':selected_date'] = $selected_date;
}

// เงื่อนไขการค้นหา
if ($search_query !== '') {
    $where_clauses[] = "(c.name LIKE :q_cus_name 
                         OR c.phone LIKE :q_cus_phone 
                         OR s.name LIKE :q_staff_name 
                         OR s.nickname LIKE :q_staff_nick)";
    
    $search_term = "%{$search_query}%";
    $params[':q_cus_name']   = $search_term;
    $params[':q_cus_phone']  = $search_term;
    $params[':q_staff_name'] = $search_term;
    $params[':q_staff_nick'] = $search_term;
}

// ประกอบคำสั่ง SQL สำหรับคิวจอง
$sql_bookings = "SELECT b.*, 
                        c.name as customer_name, c.phone as customer_phone,
                        s.nickname as staff_nickname, s.name as staff_name,
                        srv.name as default_service_name, srv.duration as default_duration, srv.price as default_price,
                        p.payment_method as payment_method,
                        p.slip_image as slip_image
                 FROM bookings b
                 LEFT JOIN customers c ON b.cus_id = c.cus_id
                 LEFT JOIN staffs s ON b.staff_id = s.staff_id
                 LEFT JOIN allservices srv ON b.service_id = srv.id
                 LEFT JOIN payments p ON b.id = p.booking_id";

if (!empty($where_clauses)) {
    $sql_bookings .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql_bookings .= " ORDER BY b.created_at DESC";

// 🟢 ดึงข้อมูลรายการคิวจองที่กรองแล้วลงตัวแปร $bookings
$stmt_b = $pdo->prepare($sql_bookings);
$stmt_b->execute($params);
$bookings = $stmt_b->fetchAll(PDO::FETCH_ASSOC);

// --- ดึงรายละเอียดบริการของการจองแต่ละรายการ (ผ่าน booking_items) ---
$booking_items_map = [];
try {
    $stmt_items = $pdo->query("SELECT bi.*, s.name as service_name, s.price, s.duration 
                               FROM booking_items bi 
                               JOIN allservices s ON bi.service_id = s.id");
    while ($row = $stmt_items->fetch(PDO::FETCH_ASSOC)) {
        $booking_items_map[$row['booking_id']][] = $row;
    }
} catch (PDOException $e) {
    $booking_items_map = [];
}

/* ฟังก์ชันค้นหาพนักงานที่ว่างตามวัน เวลา และระยะเวลาบริการ */
function getAvailableStaffs($pdo, $booking_date, $booking_time, $duration_minutes, $current_booking_id = null) {
    if (empty($booking_date) || empty($booking_time)) {
        return [];
    }

    $start_dt = date('Y-m-d H:i:s', strtotime("$booking_date $booking_time"));
    $end_dt   = date('Y-m-d H:i:s', strtotime("$start_dt + {$duration_minutes} minutes"));
    $day_of_week = date('l', strtotime($booking_date));

    $sql = "SELECT s.* 
            FROM staffs s
            WHERE (s.day_off IS NULL OR LOWER(s.day_off) != LOWER(:day_of_week))
            AND s.staff_id NOT IN (
                SELECT DISTINCT b.staff_id 
                FROM bookings b
                JOIN booking_items bi ON b.id = bi.booking_id
                JOIN allservices srv ON bi.service_id = srv.id
                WHERE b.booking_date = :b_date
                AND b.staff_id IS NOT NULL
                AND b.status IN ('pending', 'confirmed')
                " . ($current_booking_id ? "AND b.id != :current_b_id" : "") . "
                AND (
                    TIMESTAMP(b.booking_date, b.booking_time) < :end_dt
                    AND ADDDATE(TIMESTAMP(b.booking_date, b.booking_time), INTERVAL srv.duration MINUTE) > :start_dt
                )
            )
            ORDER BY s.nickname ASC";

    $stmt = $pdo->prepare($sql);
    $params = [
        ':day_of_week' => $day_of_week,
        ':b_date'      => $booking_date,
        ':start_dt'    => $start_dt,
        ':end_dt'      => $end_dt
    ];
    
    if ($current_booking_id) {
        $params[':current_b_id'] = $current_booking_id;
    }

    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// --- ดึงข้อมูลเฉพาะเมื่ออยู่ในแท็บ summary ---
if ($active_tab === 'summary') {
    $summary_date = $_GET['summary_date'] ?? date('Y-m-d');

    // 🟢 ปรับ SQL ให้ดึงหมอนวดและ JOIN เวลา Check-in ของวันนั้น พร้อมเรียงลำดับตามเวลาเช็กอิน (ใครมาก่อนขึ้นก่อน)
    $sql_summary_staffs = "
        SELECT s.*, c.checkin_time 
        FROM staffs s
        LEFT JOIN checkin c ON s.staff_id = c.staff_id AND DATE(c.checkin_time) = :sum_date
        ORDER BY 
            CASE WHEN c.checkin_time IS NULL THEN 1 ELSE 0 END ASC, 
            c.checkin_time ASC, 
            s.staff_id ASC
    ";
    
    $stmt_staffs = $pdo->prepare($sql_summary_staffs);
    $stmt_staffs->execute([':sum_date' => $summary_date]);
    $summary_staffs = $stmt_staffs->fetchAll(PDO::FETCH_ASSOC);

    // 2. ดึงรายการจองของวันที่เลือก (รวมคิวที่มีหมอรับงาน)
    $sql_summary_bookings = "
    SELECT b.id as booking_id, 
        b.staff_id, 
        b.booking_date, 
        b.booking_time, 
        b.status,
        GROUP_CONCAT(srv.name SEPARATOR ', ') as service_names,
        SUM(srv.duration) as total_duration,
        SUM(srv.price) as total_price,
        SUM(COALESCE(srv.commission, 0)) as total_hand_fee,
        COALESCE(p.payment_method, b.payment_method, 'cash') as payment_method,
        p.coupon_code
    FROM bookings b
    LEFT JOIN booking_items bi ON b.id = bi.booking_id
    LEFT JOIN allservices srv ON bi.service_id = srv.id
    LEFT JOIN payments p ON b.id = p.booking_id
    WHERE b.booking_date = :sum_date 
    AND b.status IN ('confirmed', 'completed', 'pending')
    GROUP BY b.id
    ORDER BY b.booking_time ASC
";
    
    $stmt_sum = $pdo->prepare($sql_summary_bookings);
    $stmt_sum->execute([':sum_date' => $summary_date]);
    $raw_summary_data = $stmt_sum->fetchAll(PDO::FETCH_ASSOC);

    // 3. จัดกลุ่มคิวงานตามหมอนวด (staff_id)
    $staff_queues = [];
    foreach ($raw_summary_data as $row) {
        $st_id = $row['staff_id'];
        $staff_queues[$st_id][] = $row;
    }
}
?>

    <!DOCTYPE html>
    <html lang="th">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ระบบจัดการ - Baanbodinspa Admin</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
        <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/th.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/buddhistEra.js"></script>
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
        <style>
            body { font-family: 'Sarabun', sans-serif; }
            .modal-backdrop { opacity: 0; visibility: hidden; transition: all 0.2s ease-in-out; }
            .modal-backdrop.active { opacity: 1; visibility: visible; }
            .modal-content { transform: scale(0.95); transition: all 0.2s ease-in-out; }
            .modal-backdrop.active .modal-content { transform: scale(1); }
            /* ซ่อนคลาส x-cloak สำหรับ Alpine.js */
            [x-cloak] { display: none !important; }

            .modal-backdrop {
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background-color: rgba(0, 0, 0, 0.5);
                backdrop-filter: blur(4px);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
                opacity: 0;
                visibility: hidden;
                transition: opacity 0.25s ease, visibility 0.25s ease;
            }

            .modal-backdrop.active {
                opacity: 1;
                visibility: visible;
            }

            .modal-content {
                transform: scale(0.95);
                transition: transform 0.25s ease;
                max-height: 90vh;
                overflow-y: auto;
            }

            .modal-backdrop.active .modal-content {
                transform: scale(1);
            }
        </style>
    </head>

    <body class="bg-[#F4F0EA] text-[#382214] min-h-screen flex flex-col md:flex-row">

        <!-- Sidebar ด้านซ้าย -->
        <aside class="w-64 bg-[#382214] text-[#E8E2D5] h-screen sticky top-0 flex flex-col justify-between p-4 flex-shrink-0 z-40">
            
            <!-- ส่วนบน: โลโก้ และ เมนูนำทาง -->
            <div class="space-y-6">
                <!-- โลโก้ร้าน -->
                <div class="flex items-center justify-center gap-3 px-2 py-4 border-b border-[#523826]">
                    <img src="image/logowhite.png" alt="Logo" class="h-12 w-auto">
                </div>

                <!-- รายการเมนู (Navigation List) -->
                <nav class="space-y-2">
                    <!-- ปุ่มตาราง -->
                    <button onclick="switchTab('summary')" id="tab-btn-summary"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-ml font-medium transition-colors bg-[#D2BFA6] text-[#382214]<?= $active_tab === 'summary' ?>">
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5 4a2 2 0 00-2 2v8a2 2 0 002 2h10a2 2 0 002-2V6a2 2 0 00-2-2H5zm0 2h3v3H5V6zm5 0h5v3h-5V6zm5 5h-5v3h5v-3zm-7 3H5v-3h3v3z" clip-rule="evenodd"/>
                        </svg>
                        <span>ตารางคิว</span>
                    </button>

                    <button onclick="switchTab('queue')" id="tab-btn-queue"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-ml font-medium transition-colors bg-[#D2BFA6] text-[#382214]">
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20">
                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h6a1 1 0 100-2H7zm0 4a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/>
                        </svg>
                        <span>จัดการคิวจอง</span>
                    </button>

                    <!-- ปุ่มจัดการพนักงาน -->
                    <button onclick="switchTab('staff')" id="tab-btn-staff"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-ml font-medium transition-colors hover:bg-[#523826] text-[#E8E2D5]">
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20">
                            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.04-.32.07-.65.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                        </svg>
                        <span>จัดการพนักงาน</span>
                    </button>

                    <!-- ปุ่มจัดการบริการ -->
                    <button onclick="switchTab('service')" id="tab-btn-service"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-ml font-medium transition-colors hover:bg-[#523826] text-[#E8E2D5]">
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20">
                            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z"/>
                        </svg>
                        <span>จัดการบริการ</span>
                    </button>
                </nav>
            </div>

            <!-- ส่วนล่างสุด: ปุ่มออกจากระบบ (ล็อกอยู่ล่างสุดเสมอ) -->
            <div class="pt-4 border-t border-[#523826]">
                <a href="logout.php" 
                class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-ml font-medium text-red-300 hover:bg-red-900/30 hover:text-red-200 transition-colors">
                    <svg class="w-5 h-5 stroke-current fill-none" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span>ออกจากระบบ</span>
                </a>
            </div>

        </aside>

        <!-- Main Content พื้นที่แสดงผลเนื้อหา -->
        <main class="flex-1 p-6 md:p-8 overflow-y-auto">

            <!-- Header ด้านบน -->
            <header class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
                <div class="flex flex-col gap-1 mb-8">
                    <h1 id="page-title" class="text-2xl font-bold text-[#382214]">จัดการคิวจอง (Booking Queue)</h1>
                    <i class="fa-regular fa-clock text-[#047857]"></i>
                    <span id="realtime-clock" class="font-medium text-[#382214]">กำลังโหลด...</span>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xl font-medium text-[#382214]">สวัสดี <?php echo $_SESSION['username']; ?></span>
                </div>
            </header>
            
            <?php include 'queuetable.php'; ?>
            
            <?php include 'queuemanage.php'; ?>

            <?php include 'staffmanage.php'; ?>
            
            <?php include 'servicemanage.php'; ?>
        </main>

    <script>
    function switchTab(tab) {
        const summarySec = document.getElementById('section-summary');
        const queueSec   = document.getElementById('section-queue');
        const staffSec   = document.getElementById('section-staff');
        const serviceSec = document.getElementById('section-service');

        const summaryBtn = document.getElementById('tab-btn-summary');
        const queueBtn   = document.getElementById('tab-btn-queue');
        const staffBtn   = document.getElementById('tab-btn-staff');
        const serviceBtn = document.getElementById('tab-btn-service');

        const pageTitle  = document.getElementById('page-title');

        if (summarySec) summarySec.classList.add('hidden');
        if (queueSec)   queueSec.classList.add('hidden');
        if (staffSec)   staffSec.classList.add('hidden');
        if (serviceSec) serviceSec.classList.add('hidden');

        const inactiveClass = "w-full flex items-center gap-3 px-4 py-3 rounded-xl text-ml font-medium transition-colors hover:bg-[#523826] text-[#E8E2D5]";
        const activeClass   = "w-full flex items-center gap-3 px-4 py-3 rounded-xl text-ml font-medium transition-colors bg-[#D2BFA6] text-[#382214]";

        if (summaryBtn) summaryBtn.className = inactiveClass;
        if (queueBtn)   queueBtn.className   = inactiveClass;
        if (staffBtn)   staffBtn.className   = inactiveClass;
        if (serviceBtn) serviceBtn.className = inactiveClass;

        if (tab === 'summary') {
            if (summarySec) summarySec.classList.remove('hidden');
            if (summaryBtn) summaryBtn.className = activeClass;
            if (pageTitle)  pageTitle.innerText  = "ตารางงานหมอประจำวัน (Daily Staff Schedule)";
        } else if (tab === 'queue') {
            if (queueSec)   queueSec.classList.remove('hidden');
            if (queueBtn)   queueBtn.className   = activeClass;
            if (pageTitle)  pageTitle.innerText  = "จัดการคิวจอง (Booking Queue)";
        } else if (tab === 'staff') {
            if (staffSec)   staffSec.classList.remove('hidden');
            if (staffBtn)   staffBtn.className   = activeClass;
            if (pageTitle)  pageTitle.innerText  = "จัดการพนักงาน (Staff Management)";
        } else if (tab === 'service') {
            if (serviceSec) serviceSec.classList.remove('hidden');
            if (serviceBtn) serviceBtn.className = activeClass;
            if (pageTitle)  pageTitle.innerText  = "จัดการบริการ (Service Management)";
        }
    }

    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            requestAnimationFrame(() => {
                modal.classList.add('active');
            });
        }
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
        }
    }

    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal-backdrop')) {
            event.target.classList.remove('active');
        }
    });

    function updateClock() {
        const now = new Date();
        const options = { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        };
        
        const dateString = now.toLocaleDateString('th-TH', options);
        const timeString = now.toLocaleTimeString('th-TH', { 
            hour: '2-digit', 
            minute: '2-digit', 
            second: '2-digit' 
        });

        const clockEl = document.getElementById('realtime-clock');
        if (clockEl) {
            clockEl.innerText = `${dateString} | ${timeString} น.`;
        }
    }

    let editDatePicker;

    function toggleCouponInput() {
        const methodSelect = document.getElementById('payment_method_select');
        const couponWrapper = document.getElementById('coupon_code_wrapper');
        const couponInput   = document.getElementById('coupon_code_input');

        if (methodSelect && couponWrapper) {
            if (methodSelect.value === 'coupon') {
                couponWrapper.classList.remove('hidden');
                if (couponInput) couponInput.required = true;
            } else {
                couponWrapper.classList.add('hidden');
                if (couponInput) {
                    couponInput.required = false;
                    couponInput.value = '';
                }
            }
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        updateClock();
        setInterval(updateClock, 1000);

        const urlParams = new URLSearchParams(window.location.search);
        const currentTab = urlParams.get('tab') || 'summary';
        const summaryDatePicker = document.getElementById('summary_date_picker');

        switchTab(currentTab);

        const buddhistPlugin = typeof flatpickrBuddhistEra === 'function' 
            ? new flatpickrBuddhistEra() 
            : (window.flatpickrBuddhistEra ? flatpickrBuddhistEra() : null);

        const baseConfig = {
            locale: "th",
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "j F Y",
            maxDate: "today",
            plugins: buddhistPlugin ? [buddhistPlugin] : []
        };

        const queueDatePicker = document.getElementById('queue_date_picker');
        if (queueDatePicker) {
            flatpickr("#queue_date_picker", {
                locale: "th",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "j F Y",
                plugins: typeof flatpickrBuddhistEra === 'function' ? [new flatpickrBuddhistEra()] : [],
                onChange: function(selectedDates, dateStr) {
                    document.getElementById('dateFilterForm').submit();
                }
            });
        }

        if (document.getElementById('birthdate_picker')) {
            flatpickr("#birthdate_picker", baseConfig);
        }

        if (document.getElementById('edit_birthdate_picker')) {
            editDatePicker = flatpickr("#edit_birthdate_picker", baseConfig);
        }

        if (summaryDatePicker) {
            flatpickr("#summary_date_picker", {
                locale: "th",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "j F Y",
                plugins: typeof flatpickrBuddhistEra === 'function' ? [new flatpickrBuddhistEra()] : [],
                onChange: function(selectedDates, dateStr) {
                    const form = summaryDatePicker.closest('form') || document.getElementById('summary_date_form');
                    if (form) {
                        form.submit();
                    }
                }
            });
        }
    });

    // ส่วนคำนวณของ Alpine.js (ต้องลงทะเบียนผ่าน Alpine.data)
    document.addEventListener('alpine:init', () => {
        Alpine.data('staffSkillsComponent', () => ({
            openSkillsModal: false,
            currentStaffId: null,
            currentStaffName: '',
            allServices: [],
            selectedServiceIds: [],

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
        }));
    });


    
</script>

<!-- filter หมอนวดและวันที่ ใน Quatable -->
<script>
function applyQueueFilters() {
    const staffSelect = document.getElementById('filter_staff');
    const timeSelect = document.getElementById('filter_time_range');

    const selectedStaffName = staffSelect.value.trim().toLowerCase();
    const selectedTimeRange = timeSelect.value;

    const table = document.querySelector('#section-summary table');
    if (!table) return;
    const rows = table.querySelectorAll('tbody tr');

    rows.forEach(row => {
        // ข้ามแถวรวม (<tfoot>) หรือแถวไม่มีข้อมูล
        if (row.children.length <= 1) return;

        // คอลัมน์ index 1 คือ ชื่อหมอ
        const staffCell = row.children[1];
        const staffText = staffCell ? staffCell.textContent.toLowerCase() : '';

        // 1. เช็กเงื่อนไขชื่อหมอ
        let matchStaff = !selectedStaffName || staffText.includes(selectedStaffName);

        // 2. เช็กเงื่อนไขช่วงเวลาจอง
        let matchTime = true;
        if (selectedTimeRange) {
            matchTime = false;

            // วนลูปอ่านเฉพาะคอลัมน์รอบคิว (เริ่มตั้งแต่ index 3 ข้าม index 2 ซึ่งเป็นเวลาเข้างาน)
            for (let i = 3; i < row.children.length - 5; i++) {
                const cellText = row.children[i] ? row.children[i].textContent.trim() : '';
                if (!cellText) continue;

                // ดึงเฉพาะเวลาเริ่มต้นจองคิว (ตัวเลขคู่แรกของคิว เช่น "14:00")
                const timeMatch = cellText.match(/^([01]?\d|2[0-3]):[0-5]\d/);
                if (timeMatch) {
                    const startHour = parseInt(timeMatch[0].split(':')[0]);

                    if (selectedTimeRange === 'morning' && startHour >= 10 && startHour < 13) {
                        matchTime = true;
                        break;
                    }
                    if (selectedTimeRange === 'afternoon' && startHour >= 13 && startHour < 17) {
                        matchTime = true;
                        break;
                    }
                    if (selectedTimeRange === 'evening' && startHour >= 17 && startHour <= 22) {
                        matchTime = true;
                        break;
                    }
                }
            }
        }

        // แสดงผลแถวเฉพาะเมื่อตรงตามเงื่อนไขทั้งชื่อหมอและช่วงเวลา
        row.style.display = (matchStaff && matchTime) ? '' : 'none';
    });
}

function resetQueueFilters() {
    document.getElementById('filter_staff').value = '';
    document.getElementById('filter_time_range').value = '';
    applyQueueFilters();
}
</script>
</body>

</html>