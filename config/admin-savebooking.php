<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once 'db-connect.php';

$cus_name       = $_SESSION['admin_booking']['cus_name'] ?? '';
$cus_phone      = $_SESSION['admin_booking']['cus_phone'] ?? '';
$booking_date   = $_SESSION['admin_booking']['booking_date'] ?? '';
$booking_time   = $_SESSION['admin_booking']['booking_time'] ?? '';
$customer_count = $_SESSION['admin_booking']['customer_count'] ?? 1;
$items          = $_SESSION['admin_booking']['items'] ?? [];

if (empty($cus_name) || empty($cus_phone) || empty($items)) {
    die("ข้อมูลการจองไม่สมบูรณ์ กรุณาลองทำรายการใหม่อีกครั้ง");
}

try {
    $pdo->beginTransaction();

    // 1. ค้นหา/สร้างข้อมูลลูกค้าในตาราง customers
    $stmt_cus = $pdo->prepare("SELECT cus_id FROM customers WHERE phone = :phone LIMIT 1");
    $stmt_cus->execute([':phone' => $cus_phone]);
    $cus_id = $stmt_cus->fetchColumn();

    if (!$cus_id) {
        $stmt_ins_cus = $pdo->prepare("INSERT INTO customers (name, phone) VALUES (:name, :phone)");
        $stmt_ins_cus->execute([':name' => $cus_name, ':phone' => $cus_phone]);
        $cus_id = $pdo->lastInsertId();
    }

    // 2. ตรวจสอบว่าพนักงานถูกเลือกครบทุกคนในกลุ่มหรือไม่
    $all_staff_selected = true;
    foreach ($items as $item) {
        if (empty($item['staff_id'])) {
            $all_staff_selected = false;
            break;
        }
    }

    $initial_status = $all_staff_selected ? 'confirmed' : 'pending';
    $booking_group_id = 'WALK-' . date('YmdHis') . '-' . rand(100, 999);

    // Prepare คำสั่งสำหรับตรวจสอบว่า service_id มีใน allservices จริงหรือไม่
    $stmt_check_srv = $pdo->prepare("SELECT COUNT(*) FROM allservices WHERE id = :s_id");

    // 3. เตรียมคำสั่ง INSERT ลงตาราง bookings
    $stmt = $pdo->prepare("
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

    foreach ($items as $item) {
        $staff_id    = !empty($item['staff_id']) ? (int)$item['staff_id'] : null;
        $service_ids = $item['service_ids'] ?? [];

        foreach ($service_ids as $service_id) {
            $s_id = (int)$service_id;
            
            // ตรวจสอบว่า service_id นี้มีตัวตนอยู่ในตาราง allservices จริงๆ ก่อนบันทึก
            if ($s_id > 0) {
                $stmt_check_srv->execute([':s_id' => $s_id]);
                if ($stmt_check_srv->fetchColumn() > 0) {
                    $stmt->execute([
                        ':booking_group_id' => $booking_group_id,
                        ':cus_id'           => $cus_id,
                        ':staff_id'         => $staff_id,
                        ':service_id'       => $s_id,
                        ':customer_count'   => $customer_count,
                        ':booking_date'     => $booking_date,
                        ':booking_time'     => $booking_time,
                        ':status'           => $initial_status
                    ]);
                }
            }
        }
    }

    $pdo->commit();

    // เคลียร์ Session เมื่อบันทึกสำเร็จ
    unset($_SESSION['admin_booking']);

    header("Location: dashboard.php?tab=queue&booking=success");
    exit();

} catch (PDOException $e) {
    $pdo->rollBack();
    die("เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage());
}