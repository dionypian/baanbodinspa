<?php
session_start();

require_once 'db-connect.php'; 

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $staff_id  = isset($_POST['staff_id']) ? intval($_POST['staff_id']) : 0;
    $name      = trim($_POST['name'] ?? '');
    $nickname  = trim($_POST['nickname'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $gender    = !empty($_POST['gender']) ? trim($_POST['gender']) : NULL;
    $birthdate = !empty($_POST['birthdate']) ? trim($_POST['birthdate']) : NULL;
    $day_off   = !empty($_POST['day_off']) ? trim($_POST['day_off']) : NULL;

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
            $stmt->bindValue(
                ':nickname',
                $nickname !== '' ? $nickname : null,
                $nickname !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
            );
            $stmt->bindValue(':phone', $phone, PDO::PARAM_STR);
            $stmt->bindValue(
                ':gender',
                $gender,
                $gender !== null ? PDO::PARAM_STR : PDO::PARAM_NULL
            );
            $stmt->bindValue(
                ':birthdate',
                $birthdate,
                $birthdate !== null ? PDO::PARAM_STR : PDO::PARAM_NULL
            );
            $stmt->bindValue(
                ':day_off',
                $day_off,
                $day_off !== null ? PDO::PARAM_STR : PDO::PARAM_NULL
            );
            $stmt->bindValue(':staff_id', $staff_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                $_SESSION['success'] = "อัปเดตข้อมูลพนักงานเรียบร้อยแล้ว";
            } else {
                $_SESSION['error'] = "ไม่สามารถอัปเดตข้อมูลได้";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "เกิดข้อผิดพลาดฐานข้อมูล: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "กรุณากรอกข้อมูลที่จำเป็น (ชื่อ และ เบอร์โทรศัพท์) ให้ครบถ้วน";
    }

    header("Location: dashboard.php?tab=staff");
    exit();
}
?>