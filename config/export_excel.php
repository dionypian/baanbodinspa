<?php
require_once 'db-connect.php';
date_default_timezone_set('Asia/Bangkok');

$summary_date = $_GET['summary_date'] ?? date('Y-m-d');

// 1. ดึงรายชื่อหมอนวดทั้งหมด (เรียงตามเวลา Check-in)
$sql_summary_staffs = "
    SELECT s.*, c.checkin_time 
    FROM staffs s
    LEFT JOIN checkin c ON s.staff_id = c.staff_id AND DATE(c.checkin_time) = :sum_date
    ORDER BY 
        CASE WHEN c.checkin_time IS NULL THEN 1 ELSE 0 END ASC, 
        c.checkin_time ASC, 
        s.staff_id ASC
";
$stmt_staffs = $pdo->prepare($sql_summary_staffs);
$stmt_staffs->execute([':sum_date' => $summary_date]);
$summary_staffs = $stmt_staffs->fetchAll(PDO::FETCH_ASSOC);

// 2. ดึงรายการจองของวันที่เลือก
$sql_summary_bookings = "
    SELECT b.id as booking_id, 
        b.staff_id, 
        b.booking_date, 
        b.booking_time, 
        b.status,
        GROUP_CONCAT(srv.name SEPARATOR ', ') as service_names,
        SUM(srv.duration) as total_duration,
        SUM(srv.price) as total_price,
        SUM(COALESCE(srv.commission, 0)) as total_hand_fee,
        COALESCE(p.payment_method, b.payment_method, 'cash') as payment_method,
        p.coupon_code
    FROM bookings b
    LEFT JOIN booking_items bi ON b.id = bi.booking_id
    LEFT JOIN allservices srv ON bi.service_id = srv.id
    LEFT JOIN payments p ON b.id = p.booking_id
    WHERE b.booking_date = :sum_date 
    AND b.status IN ('confirmed', 'completed', 'pending')
    GROUP BY b.id
    ORDER BY b.booking_time ASC
";
$stmt_sum = $pdo->prepare($sql_summary_bookings);
$stmt_sum->execute([':sum_date' => $summary_date]);
$raw_summary_data = $stmt_sum->fetchAll(PDO::FETCH_ASSOC);

// จัดกลุ่มคิวงานตามหมอนวด
$staff_queues = [];
foreach ($raw_summary_data as $row) {
    $st_id = $row['staff_id'];
    $staff_queues[$st_id][] = $row;
}

// หาจำนวนรอบสูงสุดที่หมอนวดได้รับงานในวันนั้น
$max_rounds = 0;
if (!empty($summary_staffs)) {
    foreach ($summary_staffs as $st) {
        $st_id = $st['staff_id'];
        $q_list = $staff_queues[$st_id] ?? [];
        if (!empty($q_list)) {
            $count = count($q_list);
            if ($count > $max_rounds) $max_rounds = $count;
        }
    }
}
if ($max_rounds === 0) $max_rounds = 1;

// กำหนด Header ให้เปิดเป็น Excel
$filename = "queue_schedule_" . $summary_date . ".xls";
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// พิมพ์ BOM สำหรับ UTF-8
echo "\xEF\xBB\xBF";
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; width: 100%; font-family: 'Sarabun', Tahoma, sans-serif; }
        th, td { border: 1px solid #333333; padding: 6px; text-align: center; vertical-align: top; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .bg-total { background-color: #e6e6e6; font-weight: bold; }
        .checkin-time { font-size: 11px; color: #047857; }
    </style>
</head>
<body>
    <h3>ตารางการจองคิววันที่ <?= date('d/m/Y', strtotime($summary_date)) ?></h3>

    <table>
        <thead>
            <tr>
                <th rowspan="2">No</th>
                <th rowspan="2">ชื่อหมอ</th>
                <th rowspan="2">เวลาเข้างาน</th>
                
                <!-- หัวตารางรอบที่ 1 ถึง N -->
                <?php for ($r = 1; $r <= $max_rounds; $r++): ?>
                    <th rowspan="2">รอบที่ <?= $r ?></th>
                <?php endfor; ?>
                
                <th colspan="4">รวมรายรับ</th>
                <th rowspan="2">ค่ามือหมอ</th>
            </tr>
            <tr>
                <th>เงินสด</th>
                <th>โอน</th>
                <th>เครดิต</th>
                <th>คูปอง</th>
            </tr>
        </thead>
        <tbody>
            <?php 
                $grand_cash = 0; $grand_transfer = 0; $grand_credit = 0; $grand_coupon = 0; $grand_hand_fee = 0;
                $no = 1; $has_active_staff = false;

                if (!empty($summary_staffs)):
                    foreach ($summary_staffs as $st): 
                        $st_id = $st['staff_id'];
                        $q_list = $staff_queues[$st_id] ?? [];

                        if (empty($q_list)) continue;

                        $has_active_staff = true;
                        $cash = 0; $transfer = 0; $credit = 0; $coupon = 0; $hand_fee = 0;

                        foreach ($q_list as $q) {
                            $price = (float)($q['total_price'] ?? 0);
                            $method = strtolower(trim($q['payment_method'] ?? 'cash'));

                            if ($method === 'cash' || $method === 'เงินสด') {
                                $cash += $price;
                            } elseif ($method === 'transfer' || $method === 'โอน' || $method === 'promptpay') {
                                $transfer += $price;
                            } elseif ($method === 'credit' || $method === 'เครดิต' || $method === 'card') {
                                $credit += $price;
                            } elseif ($method === 'coupon' || $method === 'คูปอง') {
                                $coupon += $price;
                            } else {
                                $cash += $price; 
                            }

                            $hand_fee += (int)($q['total_hand_fee'] ?? 0);
                        }

                        $grand_cash += $cash; $grand_transfer += $transfer; $grand_credit += $credit; 
                        $grand_coupon += $coupon; $grand_hand_fee += $hand_fee;

                        $checkin_str = !empty($st['checkin_time']) ? date('H:i', strtotime($st['checkin_time'])) . ' น.' : 'ยังไม่เช็กอิน';
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td class="text-left">
                        <b><?= htmlspecialchars($st['nickname'] ?? $st['name']) ?></b><br>
                    </td>
                    <td class="border border-gray-400 p-2 font-bold text-gray-800">
                            
                            <?php if (!empty($st['checkin_time'])): ?>
                                <b class="text-xs font-bold text-emerald-700 mt-0.5">
                                    <?= date('H:i', strtotime($st['checkin_time'])) ?> น.
                                </b>
                            <?php else: ?>
                                <b class="text-xs font-bold text-red-500 mt-0.5">
                                    ยังไม่เช็กอิน
                                </b>
                            <?php endif; ?>
                        </td>

                    <!-- แสดงคิวรอบต่างๆ -->
                    <?php for ($i = 0; $i < $max_rounds; $i++): ?>
                        <td class="text-left">
                            <?php if (isset($q_list[$i])): 
                                $qi = $q_list[$i];
                                $start_t  = date('H:i', strtotime($qi['booking_time']));
                                $duration = (int)($qi['total_duration'] ?? 60);
                                $end_t    = date('H:i', strtotime("+{$duration} minutes", strtotime($qi['booking_time'])));
                                $service_name = !empty($qi['service_names']) ? $qi['service_names'] : 'ไม่ระบุบริการ';
                                $price        = (float)($qi['total_price'] ?? 0);
                            ?>
                                <b><?= $start_t ?> - <?= $end_t ?></b> (<?= number_format($price) ?>฿)<br>
                                <?= htmlspecialchars($service_name) ?> (<?= $duration ?> นาที)
                            <?php endif; ?>
                        </td>
                    <?php endfor; ?>

                    <td class="text-right"><?= number_format($cash) ?></td>
                    <td class="text-right"><?= number_format($transfer) ?></td>
                    <td class="text-right"><?= number_format($credit) ?></td>
                    <td class="text-right"><?= number_format($coupon) ?></td>
                    <td class="text-right"><b><?= number_format($hand_fee) ?></b></td>
                </tr>
                <?php 
                    endforeach; 
                endif;

                if (!$has_active_staff):
                ?>
                <tr>
                    <td colspan="<?= 2 + $max_rounds + 5 ?>">ไม่มีรายการจองคิวในวันนี้</td>
                </tr>
                <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="bg-total">
                <td colspan="<?= 2 + $max_rounds ?>">รวมทั้งหมด</td>
                <td class="text-right"><?= number_format($grand_cash) ?></td>
                <td class="text-right"><?= number_format($grand_transfer) ?></td>
                <td class="text-right"><?= number_format($grand_credit) ?></td>
                <td class="text-right"><?= number_format($grand_coupon) ?></td>
                <td class="text-right"><?= number_format($grand_hand_fee) ?></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>