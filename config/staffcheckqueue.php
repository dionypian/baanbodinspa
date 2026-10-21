<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ตรวจสอบการเข้าสู่ระบบเฉพาะหมอนวด / พนักงาน
if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'db-connect.php';

$staff_id   = (int)$_SESSION['staff_id'];
$staff_name = $_SESSION['staff_nickname'] ?? $_SESSION['staff_name'] ?? 'หมอนวด';

date_default_timezone_set('Asia/Bangkok');
$selected_date = $_GET['date'] ?? date('Y-m-d');

// ดึงข้อมูลรายการคิวงานเฉพาะของหมอนวดคนนี้ (ปรับแก้ b.cus_id = c.cus_id)
$bookings = [];
try {
    $sql = "SELECT b.*, s.name AS service_name, s.duration, c.name AS customer_name, c.phone AS customer_phone 
        FROM bookings b
        LEFT JOIN allservices s ON b.service_id = s.id
        LEFT JOIN customers c ON b.cus_id = c.cus_id
        WHERE b.staff_id = :staff_id AND DATE(b.booking_time) = :booking_date
        ORDER BY b.booking_time ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':staff_id'     => $staff_id,
        ':booking_date' => $selected_date
    ]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>คิวงานของฉัน - Baanbodinspa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/th.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body { font-family: 'Sarabun', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
</head>

<body class="bg-[#FAF6F0] text-gray-800 min-h-screen flex flex-col">

    <!-- Header Navigation Bar Style เดียวกับธีมหลัก -->
    <header class="bg-[#D2BFA6] text-white p-4 sticky top-0 z-20 shadow-md">
        <div class="max-w-md mx-auto flex justify-between items-center">
            <div class="flex items-center gap-3">
                <img src="image/logo1.png" alt="Logo" class="h-9 w-auto">
            </div>
            <a href="logout.php" class="text-xs bg-red-900/80 hover:bg-red-900 px-3 py-2 rounded-xl transition-all font-medium flex items-center gap-1 shadow-sm">
                ออกจากระบบ
            </a>
        </div>
    </header>

    <!-- Main Content สำหรับใช้งานบนมือถือสะดวก -->
    <main class="flex-grow max-w-md mx-auto w-full px-4 py-5 space-y-4">

        <!-- ตัวเลือกวันที่ (Flatpickr) -->
        <div class="bg-[#FDFBF7]">
            <form id="dateForm" method="GET" action="" class="flex items-center justify-between gap-3">
                <label for="date_picker" class="font-bold text-sm text-[#382214] whitespace-nowrap">📅 เลือกวันที่:</label>
                <div class="relative flex-1">
                    <input 
                        type="text" 
                        id="date_picker" 
                        name="date" 
                        value="<?= htmlspecialchars($selected_date) ?>" 
                        readonly
                        class="w-full bg-white border border-gray-300 rounded-xl px-3.5 py-2 text-sm text-center font-bold text-[#382214] focus:outline-none focus:ring-2 focus:ring-[#047857] cursor-pointer shadow-sm"
                    >
                </div>
            </form>
        </div>

        <!-- หัวข้อสถานะประจำวัน -->
        <div class="flex justify-between items-center px-1 pt-1">
            <div class="text-left">
                <h2 class="font-bold text-base text-[#382214]">
                    คิวงานวันที่ <?= date('d/m/Y', strtotime($selected_date)) ?>
                </h2>
                <p class="text-lg font-semibold text-[#382214] mt-0.5">
                    หมอ<?= htmlspecialchars($staff_name) ?>
                </p>
            </div>
            <span class="text-xs bg-[#047857]/10 text-[#047857] font-bold px-3 py-1 rounded-full border border-[#047857]/20">
                ทั้งหมด <?= count($bookings) ?> งาน
            </span>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 border border-red-300 text-red-700 p-3 rounded-2xl text-xs text-center">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <!-- รายการคิวงาน -->
        <div class="space-y-3">
            <?php if (count($bookings) > 0): ?>
                <?php foreach ($bookings as $b): ?>
                    <?php 
                        // คำนวณช่วงเวลาเริ่มต้น - สิ้นสุด
                        $start_timestamp  = strtotime($b['booking_time']);
                        $duration_minutes = (int)($b['duration'] ?? 60);
                        $end_timestamp    = $start_timestamp + ($duration_minutes * 60);

                        $start_time = date('H:i', $start_timestamp);
                        $end_time   = date('H:i', $end_timestamp);
                        $time_range = $start_time . ' - ' . $end_time;

                        $status_bg = 'bg-amber-100 text-amber-900 border-amber-300';
                        $status_text = 'รอดำเนินการ';
                        
                        if (isset($b['status'])) {
                            if ($b['status'] === 'completed') {
                                $status_bg = 'bg-emerald-100 text-emerald-900 border-emerald-300';
                                $status_text = 'เสร็จสิ้น';
                            } elseif ($b['status'] === 'cancelled' || $b['status'] === 'canceled') {
                                $status_bg = 'bg-red-100 text-red-900 border-red-300';
                                $status_text = 'ยกเลิก';
                            } elseif ($b['status'] === 'in_progress') {
                                $status_bg = 'bg-blue-100 text-blue-900 border-blue-300';
                                $status_text = 'กำลังให้บริการ';
                            }
                        }
                    ?>

                    <div class="bg-[#FDFBF7] p-4 rounded-3xl border border-[#E8E2D5] shadow-sm space-y-3 relative overflow-hidden transition-all">
                        <!-- แถบสีซ้ายมือ -->
                        <div class="absolute left-0 top-0 bottom-0 w-2 bg-[#D2BFA6]"></div>

                        <div class="flex justify-between items-start pl-2">
                            <div>
                                <!-- แสดงช่วงเวลา เช่น 10:00 - 12:00 น. -->
                                <span class="text-xl md:text-2xl font-bold text-[#047857] tracking-tight"><?= $time_range ?> น.</span>
                                <p class="text-sm font-bold text-[#382214] mt-0.5">
                                    <?= htmlspecialchars($b['service_name'] ?? 'นวด / สปา') ?>
                                    <span class="text-xs text-gray-500 font-normal">(<?= $duration_minutes ?> นาที)</span>
                                </p>
                            </div>
                            <span class="text-xs font-bold px-2.5 py-1 rounded-full border <?= $status_bg ?>">
                                <?= $status_text ?>
                            </span>
                        </div>

                        <div class="border-t border-[#E8E2D5] pt-2.5 pl-2 text-xs text-gray-600 space-y-1.5">
                            <div class="flex items-center justify-between">
                                <span>ลูกค้า: <strong class="text-[#382214] text-sm"><?= htmlspecialchars($b['customer_name'] ?? 'ลูกค้าทั่วไป') ?></strong></span>
                            </div>

                            <?php if (!empty($b['note'])): ?>
                                <div class="text-amber-900 bg-amber-50/80 border border-amber-200/80 p-2.5 rounded-xl mt-1">
                                    📝 <strong>หมายเหตุ:</strong> <?= htmlspecialchars($b['note']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="bg-[#FDFBF7] p-8 rounded-3xl border border-[#E8E2D5] text-center text-gray-400 my-4 shadow-sm">
                    <div class="text-4xl mb-2">🌿</div>
                    <p class="text-sm font-semibold text-[#382214]">ไม่มีคิวงานในวันที่เลือก</p>
                </div>
            <?php endif; ?>
        </div>

    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            flatpickr("#date_picker", {
                locale: "th",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "j F Y",
                defaultDate: "<?= htmlspecialchars($selected_date); ?>",
                onChange: function(selectedDates, dateStr) {
                    document.getElementById('dateForm').submit();
                }
            });
        });
    </script>
</body>
</html>