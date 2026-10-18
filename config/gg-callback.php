<?php
require_once __DIR__ . '/gg-config.php';

// ตั้งค่าเชื่อมต่อ MySQL (XAMPP)
$host = "localhost";
$db_name = "massage_booking";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host={$host};dbname={$db_name};charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("เชื่อมต่อฐานข้อมูลไม่สำเร็จ: " . $e->getMessage());
}

// ตรวจสอบ Authorization Code จาก Google
if (isset($_GET['code'])) {
    $token = $googleClient->fetchAccessTokenWithAuthCode($_GET['code']);
    
    if (!isset($token['error'])) {
        $googleClient->setAccessToken($token['access_token']);
        
        // ดึงข้อมูลโปรไฟล์ลูกค้าจาก Google
        $googleService = new \Google\Service\Oauth2($googleClient);
        $userInfo = $googleService->userinfo->get();
        
        $google_id = $userInfo->id;
        $name      = $userInfo->name;
        $email     = $userInfo->email;

        // 3. ตรวจสอบว่าเคยมีลูกค้ารายนี้ในฐานข้อมูลหรือยัง
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE google_id = :google_id OR email = :email");
        $stmt->execute([':google_id' => $google_id, ':email' => $email]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($customer) {
            // กรณีเป็นลูกค้าเก่า -> อัปเดตข้อมูลล่าสุด
            $updateStmt = $pdo->prepare("UPDATE customers SET name = :name, google_id = :google_id WHERE cus_id = :cus_id");
            $updateStmt->execute([
                ':name'      => $name,
                ':google_id' => $google_id,
                ':cus_id'    => $customer['cus_id']
            ]);
            $customer_id = $customer['cus_id'];
        } else {
            // กรณีเป็นลูกค้าใหม่ -> บันทึกลงฐานข้อมูล
            $insertStmt = $pdo->prepare("INSERT INTO customers (google_id, name, email) VALUES (:google_id, :name, :email)");
            $insertStmt->execute([
                ':google_id' => $google_id,
                ':name'      => $name,
                ':email'     => $email,
            ]);
            $customer_id = $pdo->lastInsertId();
        }

        // 4. บันทึกข้อมูลลง Session สำหรับค้างสถานะล็อกอิน
        $_SESSION['user_id']      = $customer_id;
        $_SESSION['user_name']    = $name;
        $_SESSION['user_email']   = $email;

        // 5. ส่งผู้ใช้กลับไปยังหน้าแรก (หรือหน้าจองคิว)
        header('Location: booking.php');
        exit;
    } else {
        echo "เกิดข้อผิดพลาดในการรับ Token จาก Google";
    }
} else {
    header('Location: ../login.php');
    exit;
}
?>