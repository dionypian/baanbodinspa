<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db-connect.php';
require_once 'auto_assign.php';

class CouponSelectionException extends RuntimeException
{
}

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

try {
    $pdo->beginTransaction();

    if (!empty($cus_phone) && strlen($cus_phone) >= 9) {
        $stmt_chk_phone = $pdo->prepare("SELECT phone FROM customers WHERE cus_id = :cus_id");
        $stmt_chk_phone->execute([':cus_id' => $cus_id]);
        $existing_phone = trim($stmt_chk_phone->fetchColumn() ?: '');

        if (empty($existing_phone)) {
            $stmt_update_phone = $pdo->prepare("UPDATE customers SET phone = :phone WHERE cus_id = :cus_id");
            $stmt_update_phone->execute([
                ':phone'  => $cus_phone,
                ':cus_id' => $cus_id
            ]);
        }
    }

    $is_same_day = $booking_date === date('Y-m-d');
    $planned_staff_ids = $is_same_day
        ? planSameDayStaffAssignments($pdo, $booking_date, $booking_time, $items)
        : [];

    // 1. ตรวจสอบว่าทุกคนได้เลือกพนักงานครบทั้งหมดหรือไม่
    $all_staff_selected = $is_same_day;
    if (!$is_same_day) {
        $all_staff_selected = true;
        foreach ($items as $item) {
            if (empty($item['staff_id'])) {
                $all_staff_selected = false;
                break;
            }
        }
    }

    $initial_status   = $all_staff_selected ? 'confirmed' : 'pending';
    $booking_group_id = 'GRP-' . date('YmdHis') . '-' . rand(100, 999);

    // Prepare SQL สำหรับ bookings
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

    // Prepare SQL สำหรับ booking_items (เพื่อนำไปคำนวณเวลารอบนวดในหน้าตารางคิว)
    $stmt_item = $pdo->prepare("
        INSERT INTO booking_items (
            booking_id, 
            service_id
        ) VALUES (
            :booking_id, 
            :service_id
        )
    ");

    // ตัวแปรเก็บ Pool ของบริการที่จอง เพื่อจับคู่กับคูปองแบบ 1 ต่อ 1
    $all_booked_service_pool = [];

    // 2. วนลูปบันทึกตามรายบุคคล และคำนวณเวลาแบบบวกสะสมต่อกัน
    foreach ($items as $person_idx => $item) {
        $staff_id    = $is_same_day
            ? ($planned_staff_ids[$person_idx] ?? null)
            : (!empty($item['staff_id']) ? $item['staff_id'] : null);
        $service_ids = array_filter($item['service_ids'] ?? []);

        if (empty($service_ids)) continue;

        // กำหนดเวลาเริ่มต้นสำหรับบริการแรก (เช่น 11:00:00)
        $current_service_time = $booking_time;

        // วนลูปตามจำนวนบริการที่เลือกของลูกค้ารายนี้
        foreach ($service_ids as $service_id) {
            // ดึงระยะเวลา (duration) ของบริการนี้เพื่อนำไปบวกเวลารอบถัดไป
            $stmt_dur = $pdo->prepare("SELECT duration FROM allservices WHERE id = :id");
            $stmt_dur->execute([':id' => $service_id]);
            $service_duration = $stmt_dur->fetchColumn() ?: 60;

            // 2.1 INSERT ลงตาราง bookings ด้วยเวลาของรอบนั้นๆ ($current_service_time)
            $stmt_booking->execute([
                ':booking_group_id' => $booking_group_id,
                ':cus_id'           => $cus_id,
                ':staff_id'         => $staff_id,
                ':service_id'       => (int)$service_id,
                ':customer_count'   => $customer_count,
                ':booking_date'     => $booking_date,
                ':booking_time'     => $current_service_time, // เวลาเริ่มต้นของบริการนี้
                ':status'           => $initial_status
            ]);

            $booking_id = $pdo->lastInsertId();

            // 2.2 INSERT ลงตาราง booking_items
            $stmt_item->execute([
                ':booking_id'  => $booking_id,
                ':service_id' => (int)$service_id
            ]);

            // เก็บข้อมูลลง Service Pool ไว้สำหรับตรวจสอบและตัดคูปอง
            $all_booked_service_pool[] = [
                'service_id' => (int)$service_id,
                'booking_id' => $booking_id,
                'is_covered' => false
            ];

            // 🟢 คำนวณเวลาเริ่มต้นสำหรับบริการถัดไป (นำเวลาปัจจุบัน + duration นาที)
            $current_timestamp = strtotime("$booking_date $current_service_time");
            $next_timestamp    = $current_timestamp + ($service_duration * 60);
            $current_service_time = date('H:i:s', $next_timestamp);
        }
    }

    // 3. จัดการตัดใช้คูปองกรณีลูกค้าเลือกใช้หลายใบ
    $use_coupon_ids = $_POST['use_coupon_ids'] ?? [];
    if (!empty($use_coupon_ids) && is_array($use_coupon_ids)) {

        foreach ($use_coupon_ids as $use_coupon_id) {
            // 3.1 ล็อกแถวข้อมูลคูปองทีละใบ (FOR UPDATE) เพื่อกัน Race Condition
            $stmt_cp = $pdo->prepare("
                SELECT id, coupon_code, service_id, used 
                FROM cus_coupon 
                WHERE id = :id AND cus_id = :cus_id 
                FOR UPDATE
            ");
            $stmt_cp->execute([':id' => $use_coupon_id, ':cus_id' => $cus_id]);
            $cp_data = $stmt_cp->fetch(PDO::FETCH_ASSOC);

            if (!$cp_data) {
                throw new Exception("ไม่พบข้อมูลคูปองบางรายการ หรือไม่ใช่คูปองของคุณ");
            }
            if ((int)$cp_data['used'] === 1) {
                throw new Exception("คูปองรหัส [" . $cp_data['coupon_code'] . "] ถูกใช้งานไปแล้ว");
            }

            $cp_service_id = (int)$cp_data['service_id'];
            $matched_key = null;

            // 3.2 ค้นหาบริการใน Pool ที่ยังไม่ได้ใช้คูปอง และมี service_id ตรงกัน
            foreach ($all_booked_service_pool as $key => $pool_item) {
                if (!$pool_item['is_covered'] && $pool_item['service_id'] === $cp_service_id) {
                    $matched_key = $key;
                    break; 
                }
            }

            if ($matched_key === null) {
                throw new CouponSelectionException(
                    "คูปองรหัส [" . $cp_data['coupon_code'] . "] เลือกเกินจำนวนบริการประเภทนี้ที่คุณจอง"
                );
            }

            // ทำเครื่องหมายว่ารายการบริการนี้ใช้สิทธิ์คูปองเรียบร้อยแล้ว
            $all_booked_service_pool[$matched_key]['is_covered'] = true;
            $matched_booking_id = $all_booked_service_pool[$matched_key]['booking_id'];

            // 3.3 บันทึกชำระเงินลงตาราง payments ผูกกับ booking_id ของแถวนั้นๆ
            $stmt_pay = $pdo->prepare("
                INSERT INTO payments (booking_id, amount, payment_method, coupon_code, paid_at) 
                VALUES (:b_id, 0.00, 'coupon', :c_code, NOW())
            ");
            $stmt_pay->execute([
                ':b_id'   => $matched_booking_id,
                ':c_code' => $cp_data['coupon_code']
            ]);

            // 3.4 ปรับสถานะคูปองใบนี้เป็นใช้แล้ว (used = 1)
            $stmt_use = $pdo->prepare("
                UPDATE cus_coupon 
                SET used = 1
                WHERE id = :id AND cus_id = :cus_id
            ");
            $stmt_use->execute([
                ':id'     => $use_coupon_id,
                ':cus_id' => $cus_id 
            ]);
        }
    }

    if ($is_same_day) {
        autoAssignStaffToBookings($pdo, $booking_date);
    }

    $pdo->commit();
    
    unset($_SESSION['booking']);

    header("Location: bookdetail.php");
    exit;

} catch (CouponSelectionException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['booking_error'] = $e->getMessage();
    $_SESSION['booking_error_type'] = 'coupon';
    header("Location: booking.php?step=3");
    exit;
} catch (BookingAvailabilityException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['booking_error'] = $e->getMessage();
    $_SESSION['booking_error_type'] = 'availability';
    header("Location: booking.php?step=2");
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("เกิดข้อผิดพลาดที่บรรทัด " . $e->getLine() . ": " . $e->getMessage());
}