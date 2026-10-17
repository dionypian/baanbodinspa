<?php
require_once 'db-connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = $_POST['booking_id'] ?? null;

    if (!empty($booking_id)) {
        try {
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = :id1 OR id = :id2");
            $stmt->execute([
                ':id1' => $booking_id,
                ':id2' => $booking_id
            ]);

            // ดึงค่า filter_date เดิมมาด้วย เพื่อให้เมื่อกดเสร็จแล้วยังอยู่หน้าที่เลือกวันเดิมไว้
            $filter_date = $_GET['filter_date'] ?? date('Y-m-d');

            header("Location: dashboard.php?tab=queue&filter_date=" . urlencode($filter_date));
            exit();

        } catch (Exception $e) {
            die("เกิดข้อผิดพลาด: " . $e->getMessage());
        }
    } else {
        header("Location: dashboard.php?tab=queue");
        exit();
    }
}