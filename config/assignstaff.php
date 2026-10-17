<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db-connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = $_POST['booking_id'] ?? null;
    $staff_id   = !empty($_POST['staff_id']) ? $_POST['staff_id'] : null;

    if ($booking_id && $staff_id) {
        try {
            $stmt = $pdo->prepare("UPDATE bookings SET staff_id = :staff_id WHERE id = :id");
            $stmt->execute([
                ':staff_id' => $staff_id,
                ':id'       => $booking_id
            ]);
            header("Location: dashboard.php?tab=queue&status=success");
            exit;
        } catch (PDOException $e) {
            die("เกิดข้อผิดพลาด: " . $e->getMessage());
        }
    }
}

header("Location: dashboard.php?tab=queue");
exit;