<?php
session_start();
require_once 'db-connect.php'; 

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // 🟢 1. รับค่า ID แบบยืดหยุ่น (เช็คทั้ง staff_id, id และ ID)
    $staff_id = 0;
    if (isset($_POST['staff_id']) && intval($_POST['staff_id']) > 0) {
        $staff_id = intval($_POST['staff_id']);
    } elseif (isset($_POST['id']) && intval($_POST['id']) > 0) {
        $staff_id = intval($_POST['id']);
    }

    $nickname = trim($_POST['nickname'] ?? '');
    $name     = trim($_POST['name'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');

    // ถ้า name ว่าง ให้เอา nickname มาใช้แทน
    if (empty($name) && !empty($nickname)) {
        $name = $nickname;
    }

    // 2. จัดการค่า NULL สำหรับฟิลด์อื่นๆ
    $gender    = (!empty($_POST['gender'])) ? trim($_POST['gender']) : NULL;
    $birthdate = (!empty($_POST['birthdate'])) ? trim($_POST['birthdate']) : NULL;
    $day_off   = (!empty($_POST['day_off'])) ? trim($_POST['day_off']) : NULL;

    if ($staff_id > 0 && !empty($name) && !empty($phone)) {
        try {
            $sql = "UPDATE staffs 
                    SET name = :name, 
                        nickname = :nickname, 
                        phone = :phone, 
                        gender = :gender, 
                        birthdate = :birthdate, 
                        day_off = :day_off 
                    WHERE staff_id = :staff_id";

            $stmt = $pdo->prepare($sql);
            
            $stmt->bindValue(':name', $name, PDO::PARAM_STR);
            $stmt->bindValue(':nickname', $nickname !== '' ? $nickname : NULL, $nickname !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':phone', $phone, PDO::PARAM_STR);
            $stmt->bindValue(':gender', $gender, $gender !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':birthdate', $birthdate, $birthdate !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':day_off', $day_off, $day_off !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':staff_id', $staff_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                $_SESSION['success'] = "อัปเดตข้อมูลพนักงานเรียบร้อยแล้ว";
            } else {
                $_SESSION['error'] = "ไม่สามารถบันทึกข้อมูลลงฐานข้อมูลได้";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "เกิดข้อผิดพลาดฐานข้อมูล: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "ข้อมูลไม่ครบถ้วน (พนักงาน ID: {$staff_id}, ชื่อ: '{$name}', เบอร์โทร: '{$phone}')";
    }

    header("Location: dashboard.php?tab=staff");
    exit();
}
?>