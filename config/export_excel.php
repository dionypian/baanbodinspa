<?php
require_once 'db-connect.php';
date_default_timezone_set('Asia/Bangkok');

$summary_date = $_GET['summary_date'] ?? date('Y-m-d');

// ใช้รายชื่อและลำดับเดียวกับตารางคิวบนหน้า dashboard
$sql_summary_staffs = "
    SELECT s.*, c.checkin_time
    FROM staffs s
    LEFT JOIN (
        SELECT staff_id, MIN(checkin_time) AS checkin_time
        FROM checkin
        WHERE DATE(checkin_time) = :checkin_date
        GROUP BY staff_id
    ) c ON c.staff_id = s.staff_id
    WHERE c.staff_id IS NOT NULL
        OR EXISTS (
            SELECT 1
            FROM bookings b
            WHERE b.staff_id = s.staff_id
                AND b.booking_date = :booking_date
                AND b.status IN ('confirmed', 'completed', 'pending')
        )
    ORDER BY c.checkin_time IS NULL, c.checkin_time ASC, s.staff_id ASC
";
$stmt_staffs = $pdo->prepare($sql_summary_staffs);
$stmt_staffs->execute([
    ':checkin_date' => $summary_date,
    ':booking_date' => $summary_date
]);
$summary_staffs = $stmt_staffs->fetchAll(PDO::FETCH_ASSOC);

// ใช้ข้อมูลคิวชุดเดียวกับตารางคิวบนหน้า dashboard
$sql_summary_bookings = "
    SELECT
        b.id as booking_id,
        bi.item_id,
        b.staff_id,
        b.booking_date,
        b.booking_time,
        b.status,
        srv.name as service_name,
        srv.duration,
        srv.price,
        srv.commission as hand_fee,
        COALESCE(p.payment_method, b.payment_method) as payment_method,
        p.coupon_code
    FROM bookings b
    INNER JOIN booking_items bi ON b.id = bi.booking_id
    INNER JOIN allservices srv ON bi.service_id = srv.id
    LEFT JOIN payments p ON b.id = p.booking_id
    WHERE b.booking_date = :sum_date
        AND b.staff_id = :staff_id
        AND b.status IN ('confirmed', 'completed', 'pending')
    GROUP BY b.id, b.staff_id, b.booking_date, b.booking_time, b.status
    ORDER BY b.booking_time ASC, bi.item_id ASC
";
$stmt_sum = $pdo->prepare($sql_summary_bookings);

// จัดกลุ่มคิวงานตามหมอนวด
$staff_queues = [];
foreach ($summary_staffs as $st) {
    $stmt_sum->execute([
        ':sum_date' => $summary_date,
        ':staff_id' => $st['staff_id']
    ]);
    $staff_queues[$st['staff_id']] = $stmt_sum->fetchAll(PDO::FETCH_ASSOC);
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
                $no = 1;

                if (!empty($summary_staffs)):
                    foreach ($summary_staffs as $st): 
                        $st_id = $st['staff_id'];
                        $q_list = $staff_queues[$st_id] ?? [];

                        $cash = 0; $transfer = 0; $credit = 0; $coupon = 0; $hand_fee = 0;

                        foreach ($q_list as $q) {
                            $price = (float)($q['total_price'] ?? $q['price'] ?? 0);
                            $method = strtolower(trim($q['payment_method'] ?? ''));
                            if (empty($method)) {
                                continue;
                            }

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

                            $hand_fee += (int)($q['hand_fee'] ?? 0);
                        }

                        $grand_cash += $cash; $grand_transfer += $transfer; $grand_credit += $credit; 
                        $grand_coupon += $coupon; $grand_hand_fee += $hand_fee;
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td class="text-left">
                        <b><?= htmlspecialchars($st['nickname'] ?? $st['name'], ENT_QUOTES, 'UTF-8') ?></b>
                    </td>
                    <td class="text-left">
                        <?php if (!empty($st['checkin_time'])): ?>
                            <?= date('H:i', strtotime($st['checkin_time'])) ?> น.
                        <?php else: ?>
                            ยังไม่เช็กอิน
                        <?php endif; ?>
                    </td>

                    <!-- แสดงคิวรอบต่างๆ -->
                    <?php for ($i = 0; $i < $max_rounds; $i++): ?>
                        <td class="text-left">
                            <?php if (isset($q_list[$i])): 
                                $qi = $q_list[$i];
                                $start_t  = date('H:i', strtotime($qi['booking_time']));
                                $duration = (int)($qi['total_duration'] ?? $qi['duration'] ?? 60);
                                $end_t    = date('H:i', strtotime("+{$duration} minutes", strtotime($qi['booking_time'])));
                                $service_name = !empty($qi['service_names']) ? $qi['service_names'] : ($qi['service_name'] ?? 'ไม่ระบุบริการ');
                                $price        = (float)($qi['total_price'] ?? $qi['price'] ?? 0);
                                $method = strtolower(trim($qi['payment_method'] ?? ''));
                                $coupon_code = !empty($qi['coupon_code']) ? trim($qi['coupon_code']) : '';
                            ?>
                                <b><?= $start_t ?> - <?= $end_t ?></b>
                                <?php if ($method === 'coupon' || $method === 'คูปอง'): ?>
                                    <?= htmlspecialchars($coupon_code, ENT_QUOTES, 'UTF-8') ?><br>
                                <?php else: ?>
                                    (<?= number_format($price) ?>฿)<br>
                                <?php endif; ?>
                                <?= htmlspecialchars($service_name, ENT_QUOTES, 'UTF-8') ?>
                                (<?= $duration ?> นาที)
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

                if (empty($summary_staffs)):
                ?>
                <tr>
                    <td colspan="<?= 3 + $max_rounds + 5 ?>">ยังไม่มีคิวจองหรือพนักงานเช็กอินในวันที่ <?= date('d/m/Y', strtotime($summary_date)) ?></td>
                </tr>
                <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="bg-total">
                <td colspan="<?= 3 + $max_rounds ?>">รวมทั้งหมด</td>
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