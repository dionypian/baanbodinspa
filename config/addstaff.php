<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$host = "localhost";
$db_name = "massage_booking";
$username = "root";
$password = "";

$conn = new mysqli($host, $username, $password, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ตั้งค่าภาษาไทย
$conn->set_charset("utf8mb4");

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $name     = trim($_POST['name']);
    $nickname = trim($_POST['nickname']);
    $phone    = trim($_POST['phone']);
    $gender   = isset($_POST['gender']) ? trim($_POST['gender']) : NULL;
    $birthdate = !empty($_POST['birthdate']) ? $_POST['birthdate'] : null;
    $day_off   = !empty($_POST['day_off']) ? trim($_POST['day_off']) : NULL;

    // รับค่า username และ password
    $user_name = isset($_POST['username']) ? trim($_POST['username']) : '';
    $pass_word = isset($_POST['password']) ? trim($_POST['password']) : '';

    if(!empty($name) && !empty($phone) && !empty($user_name) && !empty($pass_word)) {
        // เข้ารหัสผ่าน
        $hashed_password = password_hash($pass_word, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO staffs (name, nickname, username, password, phone, gender, birthdate, day_off) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $name, $nickname, $user_name, $hashed_password, $phone, $gender, $birthdate, $day_off);

        if ($stmt->execute()) {
            echo "เพิ่มพนักงานเรียบร้อยแล้ว";
        } else {
            echo "เกิดข้อผิดพลาด: " . $conn->error;
        }

        $stmt->close();
    } else {
        echo "กรุณากรอกข้อมูลให้ครบถ้วน";
    }

    $conn->close();

    header("Location: dashboard.php?tab=staff");
    exit();
}
?>