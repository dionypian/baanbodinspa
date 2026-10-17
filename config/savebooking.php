<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db-connect.php';

$cus_id         = $_SESSION['cus_id'] ?? $_SESSION['user_id'] ?? null;
$booking_date   = $_SESSION['booking']['booking_date'] ?? '';
$booking_time   = $_SESSION['booking']['booking_time'] ?? '';
$customer_count = $_SESSION['booking']['customer_count'] ?? 1;
$items          = $_SESSION['booking']['items'] ?? [];
$user_id   = $_SESSION['user_id'] ?? 0;
$cus_phone = trim($_POST['cus_phone'] ?? '');

if (!$cus_id || empty($items) || empty($booking_date) || empty($booking_time)) {
    die("ข้อมูลการจองใน Session ไม่สมบูรณ์");
}

if (!empty($cus_phone) && $user_id > 0) {
    $stmt_update_phone = $pdo->prepare("UPDATE customers SET phone = :phone WHERE cus_id = :id AND (phone IS NULL OR phone = '')");
    $stmt_update_phone->execute([
        ':phone' => $cus_phone,
        ':id'    => $user_id
    ]);
}

try {
    $pdo->beginTransaction();

    // 1. ตรวจสอบว่าทุกคนได้เลือกพนักงานครบทั้งหมดหรือไม่
    $all_staff_selected = true;
    foreach ($items as $item) {
        if (empty($item['staff_id'])) {
            $all_staff_selected = false;
            break;
        }
    }

    $initial_status   = $all_staff_selected ? 'confirmed' : 'pending';
    $booking_group_id = 'GRP-' . date('YmdHis') . '-' . rand(100, 999);

    // Prepare SQL สำหรับ bookings (ใส่ service_id เพื่อให้ผ่าน FK Constraint)
    $stmt_booking = $pdo->prepare("
        INSERT INTO bookings (
            booking_group_id, 
            cus_id, 
            staff_id, 
            service_id,
            customer_count, 
            booking_date, 
            booking_time, 
            status, 
            created_at
        ) VALUES (
            :booking_group_id, 
            :cus_id, 
            :staff_id, 
            :service_id,
            :customer_count, 
            :booking_date, 
            :booking_time, 
            :status, 
            NOW()
        )
    ");

    // Prepare SQL สำหรับ booking_items (ไว้สำหรับ Admin ดึงรวมหลายบริการ)
    $stmt_item = $pdo->prepare("
        INSERT INTO booking_items (
            booking_id, 
            service_id
        ) VALUES (
            :booking_id, 
            :service_id
        )
    ");

    // 2. วนลูปบันทึกตามรายบุคคล
    foreach ($items as $person_idx => $item) {
        $staff_id    = !empty($item['staff_id']) ? $item['staff_id'] : null;
        $service_ids = array_filter($item['service_ids'] ?? []);

        if (empty($service_ids)) continue;

        // ดึง service_id ตัวแรกใส่ในตารางหลัก เพื่อให้ผ่าน FK Constraint
        $first_service_id = reset($service_ids);

        // 2.1 บันทึกลงตาราง bookings
        $stmt_booking->execute([
            ':booking_group_id' => $booking_group_id,
            ':cus_id'           => $cus_id,
            ':staff_id'         => $staff_id,
            ':service_id'       => $first_service_id,
            ':customer_count'   => $customer_count,
            ':booking_date'     => $booking_date,
            ':booking_time'     => $booking_time,
            ':status'           => $initial_status
        ]);

        $booking_id = $pdo->lastInsertId();

        // 2.2 บันทึกบริการทั้งหมดของลูกค้ารายนี้ลงตาราง booking_items
        foreach ($service_ids as $service_id) {
            $stmt_item->execute([
                ':booking_id'  => $booking_id,
                ':service_id' => $service_id
            ]);
        }
    }

    $pdo->commit();
    
    unset($_SESSION['booking']);

    header("Location: bookdetail.php");
    exit;

} catch (PDOException $e) {
    $pdo->rollBack();
    die("เกิดข้อผิดพลาดจาก Database: " . $e->getMessage());
}