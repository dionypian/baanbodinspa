<?php
require_once 'db-connect.php';
date_default_timezone_set('Asia/Bangkok');

function autoAssignStaffToBookings($pdo, $target_date = null) {
    if (!$target_date) {
        $target_date = date('Y-m-d');
    }

    // 1. ดึงรายชื่อหมอนวดที่ Check-in ในวันที่กำหนด (เรียงคนมาเช้าสุดขึ้นก่อน)
    $sql_checkin = "SELECT DISTINCT c.staff_id, c.checkin_time 
                    FROM checkin c
                    WHERE DATE(c.checkin_time) = :target_date
                    ORDER BY c.checkin_time ASC";
    $stmt_c = $pdo->prepare($sql_checkin);
    $stmt_c->execute([':target_date' => $target_date]);
    $checked_in_staffs = $stmt_c->fetchAll(PDO::FETCH_ASSOC);

    if (empty($checked_in_staffs)) {
        return ['status' => 'warning', 'message' => 'ยังไม่มีหมอนวดเข้างาน (Check-in) ในวันนี้'];
    }

    // Extract เอาเฉพาะ staff_id ตามลำดับมาเช้า-มาสาย
    $staff_priority_ids = array_column($checked_in_staffs, 'staff_id');

    // 2. ดึงทักษะหมอนวด (staff_services)
    $sql_skills = "SELECT staff_id, service_id FROM staff_services";
    $stmt_sk = $pdo->query($sql_skills);
    $staff_skills = [];
    while ($row = $stmt_sk->fetch(PDO::FETCH_ASSOC)) {
        $staff_skills[$row['staff_id']][] = (int)$row['service_id'];
    }

    // 3. ดึงคิวจองที่ยังไม่ได้เลือกหมอนวด (staff_id IS NULL) ในวันที่กำหนด
    // คำนวณค่าคอมมิชชันรวม (Total Commission) และระยะเวลารวม (Total Duration)
    $sql_unassigned = "
        SELECT 
            b.id AS booking_id,
            b.booking_date,
            b.booking_time,
            COALESCE(SUM(srv.commission), 0) AS total_commission,
            COALESCE(SUM(srv.duration), 0) AS total_duration,
            GROUP_CONCAT(bi.service_id) AS service_ids_str
        FROM bookings b
        JOIN booking_items bi ON b.id = bi.booking_id
        JOIN allservices srv ON bi.service_id = srv.id
        WHERE b.booking_date = :target_date
          AND (b.staff_id IS NULL OR b.staff_id = 0)
          AND b.status IN ('pending', 'confirmed')
        GROUP BY b.id
        ORDER BY total_commission DESC, b.booking_time ASC
    ";
    
    $stmt_un = $pdo->prepare($sql_unassigned);
    $stmt_un->execute([':target_date' => $target_date]);
    $unassigned_bookings = $stmt_un->fetchAll(PDO::FETCH_ASSOC);

    if (empty($unassigned_bookings)) {
        return ['status' => 'info', 'message' => 'ไม่มีคิวจองที่รอการมอบหมายหมอนวด'];
    }

    $assigned_count = 0;

    // 4. วนลูปจ่ายงานทีละใบจอง (เริ่มจากค่าคอมมิชชันสูงสุดก่อน)
    foreach ($unassigned_bookings as $booking) {
        $b_id       = $booking['booking_id'];
        $b_date     = $booking['booking_date'];
        $b_time     = $booking['booking_time'];
        $b_duration = (int)$booking['total_duration'];
        $req_services = array_map('intval', explode(',', $booking['service_ids_str']));

        // ลองหาหมอนวดที่เหมาะสมตามลำดับที่มาเช็กอินก่อน
        foreach ($staff_priority_ids as $staff_id) {
            
            // 4.1 ตรวจสอบทักษะ (ต้องทำได้ครบทุกบริการในคิวนั้น)
            $has_skills = true;
            $my_skills = $staff_skills[$staff_id] ?? [];
            foreach ($req_services as $srv_id) {
                if (!in_array($srv_id, $my_skills)) {
                    $has_skills = false;
                    break;
                }
            }
            if (!$has_skills) continue; // ทักษะไม่ตรง ข้ามไปดูหมอนวดคนถัดไป

            // 4.2 ตรวจสอบเวลาชน (Time Conflict) ว่าหมอนวดคนนี้ติดคิวอื่นอยู่ไหม
            if (isStaffAvailable($pdo, $staff_id, $b_date, $b_time, $b_duration, $b_id)) {
                // บันทึกหมอนวดลงในใบจอง และเปลี่ยนสถานะเป็น confirmed
                $sql_update = "UPDATE bookings SET staff_id = :staff_id , status = 'confirmed' WHERE id = :booking_id";
                $stmt_up = $pdo->prepare($sql_update);
                $stmt_up->execute([
                    ':staff_id'   => $staff_id,
                    ':booking_id' => $b_id
                ]);
                
                $assigned_count++;
                break; // จัดงานให้คิวนี้สำเร็จแล้ว ไปจัดคิวถัดไป
            }
        }
    }

    return [
        'status'  => 'success', 
        'message' => "จัดคิวแมตช์หมอนวดสำเร็จจำนวน {$assigned_count} รายการ"
    ];
}

/**
 * ฟังก์ชันช่วยเช็กว่า หมอนวดว่างในเวลาดังกล่าวหรือไม่
 */
function isStaffAvailable($pdo, $staff_id, $booking_date, $booking_time, $duration_minutes, $current_booking_id) {
    $start_dt = date('Y-m-d H:i:s', strtotime("$booking_date $booking_time"));
    $end_dt   = date('Y-m-d H:i:s', strtotime("$start_dt + {$duration_minutes} minutes"));

    $sql = "SELECT COUNT(*) FROM bookings b
            JOIN booking_items bi ON b.id = bi.booking_id
            JOIN allservices srv ON bi.service_id = srv.id
            WHERE b.staff_id = :staff_id
              AND b.booking_date = :b_date
              AND b.status IN ('pending', 'confirmed')
              AND b.id != :current_b_id
              AND (
                  TIMESTAMP(b.booking_date, b.booking_time) < :end_dt
                  AND ADDDATE(TIMESTAMP(b.booking_date, b.booking_time), INTERVAL srv.duration MINUTE) > :start_dt
              )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':staff_id'       => $staff_id,
        ':b_date'         => $booking_date,
        ':end_dt'         => $end_dt,
        ':start_dt'       => $start_dt,
        ':current_b_id'   => $current_booking_id
    ]);

    return $stmt->fetchColumn() == 0; // ถ้ารับคิวไม่ได้ ( overlap ) จะคืนค่า false
}