<?php
require_once 'db-connect.php';
date_default_timezone_set('Asia/Bangkok');

function autoAssignStaffToBookings($pdo, $target_date = null) {
    if (!$target_date) {
        $target_date = date('Y-m-d');
    }

    // 1. ดึงรายชื่อหมอนวดที่ Check-in (เรียงจากจำนวนงานน้อยสุด > คนมาเช้าสุดขึ้นก่อน)
    $sql_checkin = "SELECT 
                        c.staff_id, 
                        c.checkin_time,
                        COUNT(b.id) AS job_count
                    FROM checkin c
                    LEFT JOIN bookings b ON c.staff_id = b.staff_id 
                                        AND b.booking_date = :booking_date
                                        AND b.status IN ('pending', 'confirmed', 'completed')
                    WHERE DATE(c.checkin_time) = :checkin_date
                    GROUP BY c.staff_id, c.checkin_time
                    ORDER BY job_count ASC, c.checkin_time ASC";
    $stmt_c = $pdo->prepare($sql_checkin);
    $stmt_c->execute([
        ':booking_date' => $target_date,
        ':checkin_date' => $target_date
    ]);
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

                // อัปเดตอันดับหมอนวดใหม่ใน Memory ทันที เพื่อให้คิวจองถัดไปในลูปพิจารณาหมอคนอื่นที่มีงานน้อยกว่า
                $checked_in_staffs = array_map(function($st) use ($staff_id) {
                    if ($st['staff_id'] == $staff_id) {
                        $st['job_count']++;
                    }
                    return $st;
                }, $checked_in_staffs);

                // จัดเรียงลำดับใหม่ (รอบงานน้อยสุดขึ้นก่อน)
                usort($checked_in_staffs, function($a, $b) {
                    if ($a['job_count'] == $b['job_count']) {
                        return strtotime($a['checkin_time']) <=> strtotime($b['checkin_time']);
                    }
                    return $a['job_count'] <=> $b['job_count'];
                });

                $staff_priority_ids = array_column($checked_in_staffs, 'staff_id');
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
              AND b.status IN ('pending', 'confirmed', 'completed')
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

function planSameDayStaffAssignments($pdo, $booking_date, $booking_time, array $items) {
    $stmt_checkin = $pdo->prepare("
        SELECT 
            c.staff_id, 
            MIN(c.checkin_time) AS first_checkin,
            COUNT(b.id) AS existing_jobs
        FROM checkin c
        LEFT JOIN bookings b ON c.staff_id = b.staff_id 
                            AND b.booking_date = :booking_date 
                            AND b.status IN ('pending', 'confirmed', 'completed')
        WHERE DATE(c.checkin_time) = :checkin_date
        GROUP BY c.staff_id
    ");
    $stmt_checkin->execute([
        ':booking_date' => $booking_date,
        ':checkin_date' => $booking_date
    ]);

    $staff_data = $stmt_checkin->fetchAll(PDO::FETCH_ASSOC);

    if (empty($staff_data)) {
        throw new BookingAvailabilityException('ขณะนี้ไม่มีหมอนวดว่างให้บริการในช่วงเวลานี้ กรุณาเลือกวันหรือเวลาอื่น');
    }

    // สร้าง Map เก็บจำนวนงานสะสมของหมอแต่ละคน
    $staff_job_counts = [];
    foreach ($staff_data as $sd) {
        $staff_job_counts[(int)$sd['staff_id']] = [
            'count' => (int)$sd['existing_jobs'],
            'checkin_time' => strtotime($sd['first_checkin'])
        ];
    }

    $stmt_skills = $pdo->query("SELECT staff_id, service_id FROM staff_services");
    $staff_skills = [];
    while ($row = $stmt_skills->fetch(PDO::FETCH_ASSOC)) {
        $staff_skills[(int)$row['staff_id']][] = (int)$row['service_id'];
    }

    $stmt_service = $pdo->prepare("SELECT duration, commission FROM allservices WHERE id = :service_id");
    $assignment_tasks = [];
    $planned_intervals = [];
    $booking_start = strtotime("$booking_date $booking_time");

    foreach ($items as $item_index => $item) {
        $service_ids = array_values(array_filter(array_map('intval', $item['service_ids'] ?? [])));
        if (empty($service_ids)) {
            throw new BookingAvailabilityException('กรุณาเลือกบริการให้ครบถ้วน');
        }

        $duration = 0;
        $total_commission = 0;
        foreach ($service_ids as $service_id) {
            $stmt_service->execute([':service_id' => $service_id]);
            $service = $stmt_service->fetch(PDO::FETCH_ASSOC);
            if (!$service) {
                throw new BookingAvailabilityException('ไม่พบข้อมูลบริการที่เลือก กรุณาตรวจสอบรายการอีกครั้ง');
            }
            $duration += (int)$service['duration'];
            $total_commission += (float)$service['commission'];
        }

        $assignment_tasks[] = [
            'item_index' => $item_index,
            'service_ids' => $service_ids,
            'duration' => $duration,
            'total_commission' => $total_commission,
            'requested_staff_id' => !empty($item['staff_id']) ? (int)$item['staff_id'] : null
        ];
    }

    usort($assignment_tasks, static function ($a, $b) {
        $commission_order = $b['total_commission'] <=> $a['total_commission'];
        return $commission_order !== 0
            ? $commission_order
            : ($a['item_index'] <=> $b['item_index']);
    });

    $planned_assignments = [];
    foreach ($assignment_tasks as $task) {
        $item_index = $task['item_index'];
        $service_ids = $task['service_ids'];
        $duration = $task['duration'];
        $requested_staff_id = $task['requested_staff_id'];

        // เรียงลำดับหมอนวดจาก (1) จำนวนงานสะสมน้อยที่สุด -> (2) เวลาเช็กอิน
        $candidate_ids = array_keys($staff_job_counts);
        usort($candidate_ids, function($idA, $idB) use ($staff_job_counts) {
            if ($staff_job_counts[$idA]['count'] === $staff_job_counts[$idB]['count']) {
                return $staff_job_counts[$idA]['checkin_time'] <=> $staff_job_counts[$idB]['checkin_time'];
            }
            return $staff_job_counts[$idA]['count'] <=> $staff_job_counts[$idB]['count'];
        });

        $candidates = $requested_staff_id ? [$requested_staff_id] : $candidate_ids;
        $assigned_staff_id = null;

        foreach ($candidates as $staff_id) {
            if (!isset($staff_job_counts[$staff_id])) {
                continue;
            }

            $has_all_skills = true;
            foreach ($service_ids as $service_id) {
                if (!in_array($service_id, $staff_skills[$staff_id] ?? [], true)) {
                    $has_all_skills = false;
                    break;
                }
            }
            if (!$has_all_skills || !isStaffAvailable($pdo, $staff_id, $booking_date, $booking_time, $duration, 0)) {
                continue;
            }

            $booking_end = $booking_start + ($duration * 60);
            $overlaps_planned_booking = false;
            foreach ($planned_intervals[$staff_id] ?? [] as $interval) {
                if ($booking_start < $interval['end'] && $booking_end > $interval['start']) {
                    $overlaps_planned_booking = true;
                    break;
                }
            }
            if ($overlaps_planned_booking) {
                continue;
            }

            $assigned_staff_id = $staff_id;
            $planned_intervals[$staff_id][] = [
                'start' => $booking_start,
                'end' => $booking_end
            ];

            // 🟢 เพิ่มจำนวนงานสะสมของหมอคนที่ได้งาน
            $staff_job_counts[$staff_id]['count']++;
            break;
        }

        if ($assigned_staff_id === null) {
            throw new BookingAvailabilityException('ขณะนี้ไม่มีหมอนวดที่เช็กอิน มีทักษะ และว่างให้บริการในช่วงเวลานี้ กรุณาเลือกเวลาอื่น');
        }

        $planned_assignments[$item_index] = $assigned_staff_id;
    }

    return $planned_assignments;
}

class BookingAvailabilityException extends RuntimeException
{
}
