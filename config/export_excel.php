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
        bi.service_id,
        srv.name as service_name,
        srv.duration,
        srv.price,
        srv.coupon_id,
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
                <th colspan="4" rowspan="2">คิวจอง (แสดง 4 รอบต่อแถว)</th>
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
                        $booking_payment_details = [];

                        foreach ($q_list as $q) {
                            $booking_id = $q['booking_id'] ?? $q['id'];
                            $service_price = (float)($q['total_price'] ?? $q['price'] ?? 0);
                            $is_full_coverage_face_massage =
                                (int)($q['service_id'] ?? 0) === 35
                                && (int)($q['coupon_id'] ?? 0) === 3;
                            $stmt_payments = $pdo->prepare("
                                SELECT payment_id, amount, payment_method, coupon_code
                                FROM payments
                                WHERE booking_id = :b_id
                                ORDER BY payment_id ASC
                            ");
                            $stmt_payments->execute([':b_id' => $booking_id]);
                            $payment_records = $stmt_payments->fetchAll(PDO::FETCH_ASSOC);

                            $coupon_details = [];
                            $latest_channel_payments = [];
                            $pending_details = [];
                            foreach ($payment_records as $payment_record) {
                                $amount = (float)$payment_record['amount'];
                                $method = strtolower(trim($payment_record['payment_method'] ?? ''));

                                if ($method === 'coupon' || $method === 'คูปอง') {
                                    $code = trim($payment_record['coupon_code'] ?? '');
                                    if ($amount <= 0 && $code !== '') {
                                        $amount = 300;
                                    }
                                    if ($is_full_coverage_face_massage && $code !== '') {
                                        $amount = $service_price;
                                    }
                                    $codes = array_values(array_unique(array_filter(array_map('trim', explode(',', $code)))));
                                    $coupon_details[] = [
                                        'label' => $code === '' ? 'คูปอง' : 'คูปอง ' . $code,
                                        'amount' => $amount,
                                        'method' => 'coupon',
                                        'codes' => $codes,
                                        'payment_id' => (int)$payment_record['payment_id']
                                    ];
                                } elseif ($method === 'pending' && !$is_full_coverage_face_massage) {
                                    $pending_details[] = [
                                        'label' => 'ค้างชำระ',
                                        'amount' => $amount,
                                        'method' => 'pending'
                                    ];
                                } elseif (in_array($method, ['cash', 'เงินสด'], true)) {
                                    $latest_channel_payments['cash'] = [
                                        'label' => 'เงินสด',
                                        'amount' => $amount,
                                        'method' => 'cash'
                                    ];
                                } elseif (in_array($method, ['transfer', 'โอน', 'promptpay'], true)) {
                                    $latest_channel_payments['transfer'] = [
                                        'label' => 'โอน',
                                        'amount' => $amount,
                                        'method' => 'transfer'
                                    ];
                                } elseif (in_array($method, ['credit', 'เครดิต', 'card'], true)) {
                                    $latest_channel_payments['credit'] = [
                                        'label' => 'เครดิต',
                                        'amount' => $amount,
                                        'method' => 'credit'
                                    ];
                                }
                            }

                            $unique_coupon_details = [];
                            foreach ($coupon_details as $coupon_detail) {
                                $codes = $coupon_detail['codes'];
                                sort($codes);
                                $signature = empty($codes)
                                    ? 'payment-' . $coupon_detail['payment_id']
                                    : implode(',', $codes);
                                if (
                                    !isset($unique_coupon_details[$signature])
                                    || $coupon_detail['payment_id'] > $unique_coupon_details[$signature]['payment_id']
                                ) {
                                    $unique_coupon_details[$signature] = $coupon_detail;
                                }
                            }

                            $combined_coupon_codes = [];
                            foreach ($unique_coupon_details as $coupon_detail) {
                                if (count($coupon_detail['codes']) > 1) {
                                    foreach ($coupon_detail['codes'] as $code) {
                                        $combined_coupon_codes[$code] = true;
                                    }
                                }
                            }

                            $payment_details = [];
                            foreach ($unique_coupon_details as $coupon_detail) {
                                if (
                                    count($coupon_detail['codes']) === 1
                                    && isset($combined_coupon_codes[$coupon_detail['codes'][0]])
                                ) {
                                    continue;
                                }
                                $payment_details[] = [
                                    'label' => $coupon_detail['label'],
                                    'amount' => $coupon_detail['amount'],
                                    'method' => $coupon_detail['method']
                                ];
                            }
                            $coupon_amount = array_sum(array_column($payment_details, 'amount'));
                            $remaining_paid_amount = ($q['status'] ?? '') === 'completed'
                                ? max(0, $service_price - $coupon_amount)
                                : null;

                            foreach (array_reverse($latest_channel_payments) as $channel_payment) {
                                if ($remaining_paid_amount !== null) {
                                    $channel_payment['amount'] = min($channel_payment['amount'], $remaining_paid_amount);
                                    $remaining_paid_amount -= $channel_payment['amount'];
                                }
                                if ($channel_payment['amount'] > 0) {
                                    $payment_details[] = $channel_payment;
                                }
                            }

                            if (($q['status'] ?? '') !== 'completed') {
                                $payment_details = array_merge($payment_details, $pending_details);
                            }

                            if (empty($payment_records) && ($q['status'] ?? '') === 'completed') {
                                $method = strtolower(trim($q['payment_method'] ?? 'cash'));
                                $fallback = [
                                    'transfer' => ['label' => 'โอน', 'method' => 'transfer'],
                                    'โอน' => ['label' => 'โอน', 'method' => 'transfer'],
                                    'promptpay' => ['label' => 'โอน', 'method' => 'transfer'],
                                    'credit' => ['label' => 'เครดิต', 'method' => 'credit'],
                                    'เครดิต' => ['label' => 'เครดิต', 'method' => 'credit'],
                                    'card' => ['label' => 'เครดิต', 'method' => 'credit'],
                                    'coupon' => ['label' => 'คูปอง', 'method' => 'coupon'],
                                    'คูปอง' => ['label' => 'คูปอง', 'method' => 'coupon']
                                ];
                                $fallback_payment = $fallback[$method] ?? ['label' => 'เงินสด', 'method' => 'cash'];
                                $payment_details[] = array_merge($fallback_payment, ['amount' => $service_price]);
                            }

                            $booking_payment_details[$booking_id] = $payment_details;

                            if (($q['status'] ?? '') === 'completed') {
                                foreach ($payment_details as $payment_detail) {
                                    if ($payment_detail['method'] === 'cash') {
                                        $cash += $payment_detail['amount'];
                                    } elseif ($payment_detail['method'] === 'transfer') {
                                        $transfer += $payment_detail['amount'];
                                    } elseif ($payment_detail['method'] === 'credit') {
                                        $credit += $payment_detail['amount'];
                                    } elseif ($payment_detail['method'] === 'coupon') {
                                        $coupon += $payment_detail['amount'];
                                    }
                                }
                            }

                            $hand_fee += (int)($q['hand_fee'] ?? 0);
                        }

                        $grand_cash += $cash; $grand_transfer += $transfer; $grand_credit += $credit; 
                        $grand_coupon += $coupon; $grand_hand_fee += $hand_fee;

                        $queue_rows = array_chunk(array_values($q_list), 4);
                        if (empty($queue_rows)) {
                            $queue_rows = [[]];
                        }
                        $queue_row_count = count($queue_rows);
                ?>
                    <?php foreach ($queue_rows as $row_index => $round_group): ?>
                        <tr>
                            <?php if ($row_index === 0): ?>
                                <td rowspan="<?= $queue_row_count ?>"><?= $no++ ?></td>
                                <td rowspan="<?= $queue_row_count ?>" class="text-left">
                                    <b><?= htmlspecialchars($st['nickname'] ?? $st['name'], ENT_QUOTES, 'UTF-8') ?></b>
                                </td>
                                <td rowspan="<?= $queue_row_count ?>" class="text-left">
                                    <?php if (!empty($st['checkin_time'])): ?>
                                        <?= date('H:i', strtotime($st['checkin_time'])) ?> น.
                                    <?php else: ?>
                                        ยังไม่เช็กอิน
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>

                            <!-- แสดงคิว 4 รอบต่อแถว พร้อมเลขรอบ -->
                            <?php for ($column = 0; $column < 4; $column++): ?>
                                <td class="text-left">
                                    <?php if (isset($round_group[$column])):
                                        $qi = $round_group[$column];
                                        $round_number = ($row_index * 4) + $column + 1;
                                        $start_t  = date('H:i', strtotime($qi['booking_time']));
                                        $duration = (int)($qi['total_duration'] ?? $qi['duration'] ?? 60);
                                        $end_t    = date('H:i', strtotime("+{$duration} minutes", strtotime($qi['booking_time'])));
                                        $service_name = !empty($qi['service_names']) ? $qi['service_names'] : ($qi['service_name'] ?? 'ไม่ระบุบริการ');
                                        $price        = (float)($qi['total_price'] ?? $qi['price'] ?? 0);
                                        $booking_id   = $qi['booking_id'] ?? $qi['id'];
                                    ?>
                                        <b>รอบที่ <?= $round_number ?></b><br>
                                        <b><?= $start_t ?> - <?= $end_t ?></b>
                                        (<?= number_format($price) ?>฿)<br>
                                        <?= htmlspecialchars($service_name, ENT_QUOTES, 'UTF-8') ?>
                                        (<?= $duration ?> นาที)
                                        <?php foreach ($booking_payment_details[$booking_id] ?? [] as $payment_detail): ?>
                                            <br><?= htmlspecialchars($payment_detail['label'], ENT_QUOTES, 'UTF-8') ?>
                                            <?= number_format($payment_detail['amount']) ?>฿
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </td>
                            <?php endfor; ?>

                            <?php if ($row_index === 0): ?>
                                <td rowspan="<?= $queue_row_count ?>" class="text-right"><?= number_format($cash) ?></td>
                                <td rowspan="<?= $queue_row_count ?>" class="text-right"><?= number_format($transfer) ?></td>
                                <td rowspan="<?= $queue_row_count ?>" class="text-right"><?= number_format($credit) ?></td>
                                <td rowspan="<?= $queue_row_count ?>" class="text-right"><?= number_format($coupon) ?></td>
                                <td rowspan="<?= $queue_row_count ?>" class="text-right"><b><?= number_format($hand_fee) ?></b></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php 
                    endforeach; 
                endif;

                if (empty($summary_staffs)):
                ?>
                <tr>
                    <td colspan="12">ยังไม่มีคิวจองหรือพนักงานเช็กอินในวันที่ <?= date('d/m/Y', strtotime($summary_date)) ?></td>
                </tr>
                <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="bg-total">
                <td colspan="7">รวมทั้งหมด</td>
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