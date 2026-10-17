<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once 'db-connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staff_id = (int)($_POST['staff_id'] ?? 0);
    $service_ids = $_POST['services'] ?? [];

    if ($staff_id <= 0) {
        die("ข้อมูลพนักงานไม่ถูกต้อง");
    }

    try {
        $pdo->beginTransaction();

        // ลบทักษะเดิมของหมอนวดคนนี้ก่อน
        $stmt_del = $pdo->prepare("DELETE FROM staff_services WHERE staff_id = :staff_id");
        $stmt_del->execute([':staff_id' => $staff_id]);

        // บันทึกลิสต์ทักษะใหม่
        if (!empty($service_ids)) {
            $stmt_ins = $pdo->prepare("INSERT INTO staff_services (staff_id, service_id) VALUES (:staff_id, :service_id)");
            foreach ($service_ids as $s_id) {
                $stmt_ins->execute([
                    ':staff_id'   => $staff_id,
                    ':service_id' => (int)$s_id
                ]);
            }
        }

        $pdo->commit();
        header("Location: dashboard.php?tab=staff");
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        die("เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage());
    }
}