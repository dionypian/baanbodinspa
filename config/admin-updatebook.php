<?php
require_once 'db-connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id  = $_POST['booking_id'] ?? null;
    $staff_id    = !empty($_POST['staff_id']) ? $_POST['staff_id'] : null;
    $service_ids = $_POST['service_ids'] ?? [];

    if (!empty($booking_id)) {
        try {
            $pdo->beginTransaction();

            // 1. อัปเดต staff_id และสถานะในตาราง bookings (ใช้คอลัมน์ id)
            if (!empty($staff_id)) {
                $stmt_staff = $pdo->prepare("UPDATE bookings SET staff_id = :staff_id, status = 'confirmed' WHERE id = :b_id");
                $stmt_staff->execute([
                    ':staff_id' => $staff_id, 
                    ':b_id'     => $booking_id
                ]);
            } else {
                $stmt_staff = $pdo->prepare("UPDATE bookings SET staff_id = NULL WHERE id = :b_id");
                $stmt_staff->execute([
                    ':b_id' => $booking_id
                ]);
            }

            // 2. อัปเดตรายการบริการในตาราง booking_items
            if (!empty($service_ids)) {
                // ลบรายการบริการเดิมของการจองนี้
                $stmt_del = $pdo->prepare("DELETE FROM booking_items WHERE booking_id = :b_id");
                $stmt_del->execute([':b_id' => $booking_id]);

                // แทรกรายการบริการใหม่
                $stmt_ins = $pdo->prepare("INSERT INTO booking_items (booking_id, service_id) VALUES (:b_id, :s_id)");
                foreach ($service_ids as $s_id) {
                    $stmt_ins->execute([
                        ':b_id' => $booking_id, 
                        ':s_id' => $s_id
                    ]);
                }
            }

            $pdo->commit();
            header("Location: dashboard.php?tab=queue&status=success");
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            die("เกิดข้อผิดพลาด: " . $e->getMessage());
        }
    }
}