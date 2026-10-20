<?php
session_start();

require_once 'gg-config.php';
require_once 'db-connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = isset($_POST['identifier']) && is_string($_POST['identifier'])
        ? trim($_POST['identifier'])
        : '';
    $password = isset($_POST['password']) && is_string($_POST['password'])
        ? $_POST['password']
        : '';

    if (!empty($identifier) && !empty($password)) {
        try {
            // 1. ตรวจสอบในตาราง admins
            $stmtAdmin = $pdo->prepare("SELECT * FROM admins WHERE username = :id1 OR email = :id2 LIMIT 1");
            $stmtAdmin->execute([
                ':id1' => $identifier,
                ':id2' => $identifier
            ]);
            $admin = $stmtAdmin->fetch(PDO::FETCH_ASSOC);

            if ($admin) {
                // ตรวจสอบรหัสผ่าน (รองรับทั้ง password_hash และ ข้อความธรรมดา)
                $adminPassword = $admin['password'] ?? null;
                $isPasswordCorrect = is_string($adminPassword)
                    && (password_verify($password, $adminPassword) || $password === $adminPassword);

                if ($isPasswordCorrect) {
                    $_SESSION['user_id']  = $admin['admin_id'];
                    $_SESSION['username'] = $admin['username'];
                    $_SESSION['fullname'] = $admin['fullname'] ?? '';
                    $_SESSION['role']     = 'admin';

                    header("Location: dashboard.php");
                    exit();
                }
            }

            // 2. ถ้าไม่ใช่แอดมิน ให้ตรวจสอบในตาราง customers
            $stmtUser = $pdo->prepare("SELECT * FROM customers WHERE email = :email OR username = :username OR phone = :phone LIMIT 1");
            $stmtUser->execute([
                ':email' => $identifier,
                ':username' => $identifier,
                ':phone' => $identifier
            ]);
            $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $userPassword = $user['password'] ?? null;
                $isUserPasswordCorrect = is_string($userPassword)
                    && (password_verify($password, $userPassword) || $password === $userPassword);

                if ($isUserPasswordCorrect) {
                    $_SESSION['user_id']  = $user['cus_id'];
                    $_SESSION['username'] = $user['username'] ?? $user['email'];
                    $_SESSION['role']     = 'user';

                    header("Location: index.php");
                    exit();
                }
            }

            // ถ้ารหัสผ่านไม่ถูกต้อง หรือไม่พบข้อมูลในระบบ
            $error = 'ชื่อผู้ใช้ เบอร์โทรศัพท์ หรือรหัสผ่านไม่ถูกต้อง';

        } catch (PDOException $e) {
            $error = 'ไม่สามารถเข้าสู่ระบบได้ในขณะนี้ กรุณาลองใหม่อีกครั้ง';
        }
    } else {
        $error = 'กรุณากรอกชื่อผู้ใช้หรือเบอร์โทรศัพท์ และรหัสผ่าน';
    }
}

$googleLoginUrl = isset($googleClient) ? $googleClient->createAuthUrl() : '#';
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - Baanbodinspa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
        }
    </style>
</head>

<body class="bg-[#FAF6F0] text-gray-800 min-h-screen flex flex-col">

    <!-- Navbar อยู่บนสุดเสมอ -->
    <?php include 'includes/nav2.php'; ?>

    <!-- Background Pattern -->
    <div class="fixed inset-0 pointer-events-none opacity-20 z-0">
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

    <!-- Container สำหรับจัดให้ Card Login อยู่ตรงกลาง -->
    <main class="flex-grow flex items-center justify-center p-4 relative z-10">
        
        <div class="w-full max-w-lg bg-[#FDFBF7] rounded-3xl shadow-2xl border border-[#E8E2D5] p-6 md:p-8 relative animate-fade-in my-8">

            <!-- Header -->
            <div class="flex flex-col items-center justify-center text-center mb-6">
                <img src="image/logo1.png" alt="Baan Bodin Spa Logo"
                    class="h-14 w-auto object-contain transition-transform duration-300 mx-auto mb-6">
                <h1 class="text-xl font-semibold text-[#382214]">เข้าสู่ระบบ</h1>
            </div>

            <!-- กล่องแสดงข้อความเตือนเมื่อ Login ผิดพลาด -->
            <?php if (!empty($error)): ?>
                <div class="mb-4 p-3 bg-red-100 border border-red-300 text-red-700 text-xs md:text-sm rounded-lg text-center">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Form Start (ระบุ action และ method) -->
            <form action="login.php" method="POST" class="space-y-4">

                <!-- Email/Username Input -->
                <div>
                    <label class="flex items-center gap-1.5 text-sm font-medium text-[#382214] mb-1.5">
                        <img src="image/email.png" alt="Email Icon" class="h-4 w-4 object-contain shrink-0">
                        <span>ชื่อผู้ใช้ หรือ เบอร์โทรศัพท์</span>
                    </label>
                    <input type="text" name="identifier" required placeholder="กรอกชื่อผู้ใช้ หรือ เบอร์โทรศัพท์"
                        class="w-full px-3.5 py-2.5 text-sm rounded-md border border-gray-300 focus:outline-none focus:ring-1 focus:ring-[#382214] focus:border-transparent bg-white shadow-sm">
                </div>

                <!-- Password Input -->
                <div>
                    <label class="flex items-center gap-1.5 text-sm font-medium text-[#382214] mb-1.5">
                        <img src="image/padlock.png" alt="Padlock Icon" class="h-3.5 w-3.5 object-contain shrink-0">
                        <span>รหัสผ่าน</span>
                    </label>
                    <input type="password" name="password" required placeholder="กรอกรหัสผ่าน"
                        class="w-full px-3.5 py-2.5 text-sm rounded-md border border-gray-300 focus:outline-none focus:ring-1 focus:ring-[#382214] focus:border-transparent bg-white shadow-sm">
                </div>

                <!-- Submit Button -->
                <button type="submit"
                    class="w-full py-3 bg-[#047857] hover:bg-[#244735] text-white font-medium rounded-md shadow-md transition text-sm mt-3">
                    เข้าสู่ระบบ
                </button>

                <!-- Divider -->
                <div class="relative flex py-2 items-center mb-5 opacity-70">
                    <div class="flex-grow border-t border-[#382214]"></div>
                    <span class="flex-shrink mx-3 text-sm text-[#382214]">หรือเข้าสู่ระบบผ่าน Google</span>
                    <div class="flex-grow border-t border-[#382214]"></div>
                </div>

                <!-- Quick Social Login -->
                <div class="flex justify-center mb-5">
                    <!-- Google Button -->
                    <a href="<?php echo htmlspecialchars($googleLoginUrl); ?>"
                        class="py-2.5 px-6 bg-[#DB4437] hover:bg-[#c5372b] text-white text-xs font-bold rounded-md flex items-center justify-center gap-1.5 transition shadow-sm uppercase tracking-wider">
                        <img src="image/google.png" alt="Google Icon" class="h-5 w-5 object-contain shrink-0">
                        <span>GOOGLE</span>
                    </a>
                </div>
            </form>
        </div>

    </main>

</body>

</html>