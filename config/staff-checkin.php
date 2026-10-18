<?php
session_start();
require_once 'db-connect.php';
require_once 'auto_assign.php';

date_default_timezone_set('Asia/Bangkok');
$today = date('Y-m-d');

// ดึงข้อความแจ้งเตือนจาก Session (ถ้ามี) แล้วเคลียร์ทิ้ง
$msg = $_SESSION['flash_msg'] ?? '';
$msg_type = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_msg'], $_SESSION['flash_type']);

// บันทึกข้อมูล Check-in เมื่อส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staff_id = $_POST['staff_id'] ?? null;
    
    if (!$staff_id) {
        $_SESSION['flash_msg'] = "กรุณาเลือกชื่อของคุณ";
        $_SESSION['flash_type'] = "error";
    } else {
        // 🟢 ดึงชื่อเล่นหรือชื่อจริงของพนักงานมาใช้ตั้งชื่อไฟล์รูป
        $stmt_name = $pdo->prepare("SELECT name, nickname FROM staffs WHERE staff_id = :id LIMIT 1");
        $stmt_name->execute([':id' => $staff_id]);
        $staff_info = $stmt_name->fetch(PDO::FETCH_ASSOC);

        // เลือกใช้ชื่อเล่น ถ้าไม่มีให้ใช้ชื่อจริง (และแปลงเว้นวรรคเป็น _ เพื่อความปลอดภัยของชื่อไฟล์)
        $raw_name = !empty($staff_info['nickname']) ? $staff_info['nickname'] : ($staff_info['name'] ?? 'staff');
        $safe_staff_name = preg_replace('/[^\p{L}\p{N}]/u', '_', $raw_name);

        // เช็กก่อนว่าวันนี้พนักงานคนนี้เคยเช็กอินไปแล้วหรือยัง
        $stmt_check = $pdo->prepare("SELECT id FROM checkin WHERE staff_id = :staff_id AND DATE(checkin_time) = :today LIMIT 1");
        $stmt_check->execute([
            ':staff_id' => $staff_id,
            ':today'    => $today
        ]);
        
        if ($stmt_check->fetch()) {
            $_SESSION['flash_msg'] = "คุณได้ทำการลงเวลาเข้างานของวันนี้ไปเรียบร้อยแล้วค่ะ!";
            $_SESSION['flash_type'] = "error";
        } else {
            // ตรวจสอบการอัปโหลดรูปถ่าย
            if (isset($_FILES['staff_photo']) && $_FILES['staff_photo']['error'] === UPLOAD_ERR_OK) {
                $fileTmp = $_FILES['staff_photo']['tmp_name'];
                $fileName = $_FILES['staff_photo']['name'];
                $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                if (in_array($ext, $allowed)) {
                    
                    // สร้างโฟลเดอร์ uploads/checkin ถ้ายังไม่มี
                    $uploadDir = 'uploads/checkin/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    // 🟢 เปลี่ยนชื่อไฟล์เป็น: checkin_ชื่อพนักงาน_ปีเดือนวัน_เวลา.นามสกุล
                    $newFileName = 'checkin_' . $safe_staff_name . '_' . date('Ymd_His') . '.' . $ext;
                    $destPath = $uploadDir . $newFileName;
                    
                    if (move_uploaded_file($fileTmp, $destPath)) {
                        try {
                            // บันทึกลงตาราง checkin
                            $stmt = $pdo->prepare("
                                INSERT INTO checkin (staff_id, checkin_time, image)
                                VALUES (:staff_id, NOW(), :photo)
                            ");
                            $stmt->execute([
                                ':staff_id' => $staff_id,
                                ':photo'    => $destPath
                            ]);

                            $_SESSION['flash_msg'] = "เข้างานเรียบร้อยแล้วค่ะ! ขอให้เป็นวันที่ดีนะคะ ✨";
                            $_SESSION['flash_type'] = "success";

                            try {
                                autoAssignStaffToBookings($pdo, $today);
                            } catch (Exception $assignment_error) {
                                error_log('Auto-assignment after staff check-in failed: ' . $assignment_error->getMessage());
                                $_SESSION['flash_msg'] = "บันทึกเข้างานสำเร็จ แต่จัดคิวอัตโนมัติไม่สำเร็จ กรุณาแจ้งผู้ดูแลระบบ";
                                $_SESSION['flash_type'] = "error";
                            }
                        } catch (Exception $e) {
                            $_SESSION['flash_msg'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage();
                            $_SESSION['flash_type'] = "error";
                        }
                    } else {
                        $_SESSION['flash_msg'] = "ไม่สามารถบันทึกไฟล์รูปภาพได้";
                        $_SESSION['flash_type'] = "error";
                    }
                } else {
                    $_SESSION['flash_msg'] = "รองรับเฉพาะไฟล์รูปภาพ (JPG, PNG, WEBP) เท่านั้น";
                    $_SESSION['flash_type'] = "error";
                }
            } else {
                $_SESSION['flash_msg'] = "กรุณาถ่ายรูปหรืออัปโหลดรูปถ่ายเข้างาน";
                $_SESSION['flash_type'] = "error";
            }
        }
    }
    
    header("Location: staff-checkin.php");
    exit();
}

// ดึงรายชื่อพนักงานทั้งหมด
$stmt_staff = $pdo->query("SELECT staff_id, name, nickname FROM staffs ORDER BY nickname ASC, name ASC");
$staffs = $stmt_staff->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลงเวลาเข้างานพนักงาน - Baanbodinspa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Sarabun', sans-serif; } </style>
</head>
<body class="bg-[#FAF6F0] min-h-screen flex items-center justify-center p-4">

    <div class="bg-white w-full max-w-md rounded-3xl shadow-xl border border-[#E8E2D5] overflow-hidden p-6 space-y-6">
        
        <div class="text-center space-y-1">
            <h1 class="text-2xl font-bold text-[#382214]">ลงเวลาเข้างาน (Check-in)</h1>
            <p class="text-sm text-gray-500">ประจำวันที่ <?= date('d/m/Y') ?></p>
        </div>

        <?php if ($msg): ?>
            <div class="p-4 rounded-2xl text-center font-bold text-sm <?= $msg_type === 'success' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-rose-100 text-rose-800 border border-rose-200' ?>">
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <form action="staff-checkin.php" method="POST" enctype="multipart/form-data" class="space-y-5">
            
            <!-- 1. เลือกชื่อพนักงาน -->
            <div>
                <label class="block text-sm font-bold text-[#382214] mb-1.5">เลือกชื่อพนักงาน <span class="text-red-500">*</span></label>
                <select name="staff_id" required class="w-full rounded-2xl border border-gray-300 p-3 bg-white text-[#382214] font-medium text-base focus:outline-none focus:ring-2 focus:ring-[#382214]">
                    <option value="">-- คลิกเพื่อเลือกชื่อของคุณ --</option>
                    <?php foreach ($staffs as $st): ?>
                        <option value="<?= $st['staff_id'] ?>">
                            หมอ <?= htmlspecialchars($st['nickname'] ?? $st['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- 2. ถ่ายรูปเซลฟี่เข้างาน -->
            <div>
                <label class="block text-sm font-bold text-[#382214] mb-1.5">2. ถ่ายรูปหน้าตรงเข้างาน <span class="text-red-500">*</span></label>
                <!-- capture="user" บังคับเปิดกล้องหน้าสำหรับเซลฟี่บนมือถือทันที -->
                <input type="file" name="staff_photo" accept="image/*" capture="user" required
                       class="w-full border border-gray-300 rounded-2xl p-2.5 text-sm bg-gray-50 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-[#382214] file:text-white hover:file:bg-[#5A3A29]">
            </div>

            <button type="submit" class="w-full bg-[#047857] hover:bg-[#065f46] text-white py-3.5 rounded-2xl font-bold text-base shadow-lg transition-all active:scale-95">
                📸 บันทึกเข้างาน (Check-in)
            </button>

        </form>

    </div>

</body>
</html>