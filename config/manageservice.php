<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "massage_booking");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $category    = trim($_POST['category'] ?? '');
        $group_name  = trim($_POST['group_name'] ?? '');
        $name        = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $duration    = intval($_POST['duration'] ?? 60);
        $price       = floatval($_POST['price'] ?? 0);
        $commission  = intval($_POST['commission'] ?? 0);
        $status      = $_POST['status'] ?? 'active';

        if (!empty($group_name)) {
            $stmt = $conn->prepare("INSERT INTO allservices (category, group_name, name, description, duration, price, commission, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            // ชนิดข้อมูล: s(cat), s(group), s(name), s(desc), i(dur), d(price), i(comm), s(status)
            $stmt->bind_param("ssssidis", $category, $group_name, $name, $description, $duration, $price, $commission, $status);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "เพิ่มรายการบริการเรียบร้อยแล้ว";
            } else {
                $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $stmt->error;
            }
            $stmt->close();
        }
    } 
    elseif ($action === 'edit') {
        $id          = intval($_POST['id'] ?? $_POST['service_id'] ?? 0);
        $category    = trim($_POST['category'] ?? '');
        $group_name  = trim($_POST['group_name'] ?? '');
        $name        = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $duration    = intval($_POST['duration'] ?? 60);
        $price       = floatval($_POST['price'] ?? 0);
        $commission  = intval($_POST['commission'] ?? 0);
        $status      = $_POST['status'] ?? 'active';

        if ($id > 0 && !empty($group_name)) {
            $stmt = $conn->prepare("UPDATE allservices SET category = ?, group_name = ?, name = ?, description = ?, duration = ?, price = ?, commission = ?, status = ? WHERE id = ?");
            
            // 🟢 แก้ไขตรงนี้: เปลี่ยนจาก "ssssidssi" เป็น "ssssidisi" (i ตัวที่ 7 คือ $commission)
            $stmt->bind_param("ssssidisi", $category, $group_name, $name, $description, $duration, $price, $commission, $status, $id);

            if ($stmt->execute()) {
                $_SESSION['success'] = "อัปเดตข้อมูลบริการเรียบร้อยแล้ว";
            } else {
                $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['error'] = "ข้อมูลไม่ถูกต้อง (ID: $id)";
        }
    } 
    elseif ($action === 'delete') {
        $service_id = intval($_POST['id'] ?? $_POST['service_id'] ?? 0);

        if ($service_id > 0) {
            $stmt = $conn->prepare("DELETE FROM allservices WHERE id = ?");
            $stmt->bind_param("i", $service_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "ลบรายการบริการเรียบร้อยแล้ว";
            } else {
                $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $stmt->error;
            }
            $stmt->close();
        }
    }   

    $conn->close();
    header("Location: dashboard.php?tab=service");
    exit();
}
?>