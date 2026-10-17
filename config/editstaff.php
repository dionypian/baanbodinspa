<?php
session_start();

$conn = new mysqli("localhost", "root", "", "massage_booking");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $staff_id   = intval($_POST['staff_id']);
    $name       = trim($_POST['name']);
    $nickname   = trim($_POST['nickname']);
    $phone      = trim($_POST['phone']);
    $gender     = isset($_POST['gender']) ? trim($_POST['gender']) : NULL;
    $birthdate  = !empty($_POST['birthdate']) ? $_POST['birthdate'] : NULL;
    $day_off    = isset($_POST['day_off']) ? trim($_POST['day_off']) : NULL;

    if ($staff_id > 0 && !empty($name) && !empty($phone)) {
        // คำสั่ง UPDATE ข้อมูล
        $stmt = $conn->prepare("UPDATE staffs SET name=?, nickname=?, phone=?, gender=?, birthdate=?, day_off=? WHERE staff_id=?");
        $stmt->bind_param("ssssssi", $name, $nickname, $phone, $gender, $birthdate, $day_off, $staff_id);

        if ($stmt->execute()) {
            $_SESSION['success'] = "อัปเดตข้อมูลพนักงานเรียบร้อยแล้ว";
        } else {
            $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $stmt->error;
        }
        $stmt->close();
    }
    $conn->close();

    // บันทึกเสร็จให้ส่งกลับหน้าหลักที่แท็บพนักงาน
    header("Location: dashboard.php?tab=staff");
    exit();
}
?>