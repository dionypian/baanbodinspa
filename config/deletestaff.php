<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // header("Location: login.php");
    // exit();
}

$conn = new mysqli("localhost", "root", "", "massage_booking");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $staff_id = isset($_POST['staff_id']) ? intval($_POST['staff_id']) : 0;

    if ($staff_id > 0) {
        // เตรียมคำสั่งลบข้อมูล
        $stmt = $conn->prepare("DELETE FROM staffs WHERE staff_id = ?");
        $stmt->bind_param("i", $staff_id);

        if ($stmt->execute()) {
            $_SESSION['success'] = "ลบข้อมูลพนักงานเรียบร้อยแล้ว";
        } else {
            $_SESSION['error'] = "เกิดข้อผิดพลาดในการลบ: " . $stmt->error;
        }
        $stmt->close();
    }
    $conn->close();

    // ลบเสร็จส่งกลับหน้าหลักพร้อมเปิดแท็บพนักงาน
    header("Location: dashboard.php?tab=staff");
    exit();
}
?>