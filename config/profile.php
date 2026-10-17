<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ตรวจสอบการล็อกอิน (เช็ค session user/customer)
if (!isset($_SESSION['user_id']) && !isset($_SESSION['cus_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'db-connect.php';

// ดึง ID ผู้ใช้จาก Session (รองรับทั้ง cus_id และ user_id)
$cus_id = $_SESSION['cus_id'] ?? $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// --- ดึงข้อมูลลูกค้าจากตาราง customers ---
try {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE cus_id = :cus_id");
    $stmt->execute([':cus_id' => $cus_id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        die("ไม่พบข้อมูลผู้ใช้งานในระบบ");
    }
} catch (PDOException $e) {
    die("เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: " . $e->getMessage());
}

// --- บันทึกการแก้ไขข้อมูลส่วนตัว ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'update_info') {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($name) || empty($phone)) {
        $error_message = 'กรุณากรอกชื่อ-นามสกุล และเบอร์โทรศัพท์';
    } else {
        try {
            // เช็คว่าเบอร์โทรซ้ำกับลูกค้ารายอื่นหรือไม่
            $stmt_chk = $pdo->prepare("SELECT cus_id FROM customers WHERE phone = :phone AND cus_id != :cus_id");
            $stmt_chk->execute([':phone' => $phone, ':cus_id' => $cus_id]);

            if ($stmt_chk->fetch()) {
                $error_message = 'เบอร์โทรศัพท์นี้ถูกใช้งานแล้วโดยลูกค้ารายอื่น';
            } else {
                // อัปเดตข้อมูลลงตาราง customers
                $sql = "UPDATE customers SET 
                        name = :name, 
                        email = :email, 
                        phone = :phone, 
                        updated_at = NOW() 
                        WHERE cus_id = :cus_id";
                
                $stmt_up = $pdo->prepare($sql);
                $stmt_up->execute([
                    ':name'   => $name,
                    ':email'  => $email,
                    ':phone'  => $phone,
                    ':cus_id' => $cus_id
                ]);

                // อัปเดตชื่อใน Session
                $_SESSION['user_name'] = $name;
                
                $success_message = 'อัปเดตข้อมูลส่วนตัวเรียบร้อยแล้ว';
                
                // ดึงข้อมูลใหม่มาแสดง
                $stmt->execute([':cus_id' => $cus_id]);
                $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            $error_message = 'เกิดข้อผิดพลาดในการบันทึก: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข้อมูลส่วนตัว - Baanbodinspa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body { font-family: 'Sarabun', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-[#FAF6F0] text-gray-800 min-h-screen flex flex-col">

    <?php include 'includes/nav3.php'; ?>

    <main class="flex-grow flex items-center justify-center p-4 sm:p-6 my-6">
        <div class="w-full max-w-xl bg-white rounded-3xl shadow-xl border border-[#E8E2D5] overflow-hidden">

            <!-- Header โปรไฟล์ -->
            <div class="bg-white text-[#382214] border p-6 text-center relative">
                <h1 class="text-xl font-bold">แก้ไขข้อมูลส่วนตัว</h1>
            </div>

            <div class="p-6">

                <!-- Alert แจ้งเตือน -->
                <?php if (!empty($success_message)): ?>
                    <div class="bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded-2xl mb-5 text-sm flex items-center gap-2">
                        <span>✅</span> <?= htmlspecialchars($success_message) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded-2xl mb-5 text-sm flex items-center gap-2">
                        <span>⚠️</span> <?= htmlspecialchars($error_message) ?>
                    </div>
                <?php endif; ?>

                <!-- ฟอร์มแก้ไขข้อมูล -->
                <form action="profile.php" method="POST" class="space-y-4">
                    <input type="hidden" name="action_type" value="update_info">

                    <div>
                        <label class="block text-xs font-semibold text-[#382214] mb-1">ชื่อ-นามสกุล <span class="text-red-500">*</span></label>
                        <input type="text" name="name" required value="<?= htmlspecialchars($customer['name'] ?? '') ?>"
                            class="w-full rounded-xl border border-gray-300 p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#382214]">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#382214] mb-1">อีเมล (Email)</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($customer['email'] ?? '') ?>" placeholder="example@email.com"
                            class="w-full rounded-xl border border-gray-300 p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#382214]">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#382214] mb-1">เบอร์โทรศัพท์ <span class="text-red-500">*</span></label>
                        <input type="tel" name="phone" required value="<?= htmlspecialchars($customer['phone'] ?? '') ?>" placeholder="0812345678"
                            class="w-full rounded-xl border border-gray-300 p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#382214]">
                    </div>

                    <div class="pt-3">
                        <button type="submit" class="w-full bg-[#047857] hover:bg-[#065f46] text-white py-3 rounded-xl font-bold text-sm shadow-md transition-colors active:scale-95">
                             บันทึกข้อมูลส่วนตัว
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </main>

</body>
</html>