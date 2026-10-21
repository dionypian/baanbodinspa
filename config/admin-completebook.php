<?php
require_once 'db-connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id     = $_POST['booking_id'] ?? null;
    $payment_method = $_POST['payment_method'] ?? 'cash';
    $coupon_code    = $_POST['coupon_code'] ?? null;

    if (!empty($booking_id)) {
        try {
            $pdo->beginTransaction();

            // 1. จัดการอัปโหลดไฟล์สลิป (ถ้ามีส่งมา)
            $slip_filename = null;
            if (isset($_FILES['payment_slip']) && $_FILES['payment_slip']['error'] === UPLOAD_ERR_OK) {
                $file_tmp  = $_FILES['payment_slip']['tmp_name'];
                $file_name = $_FILES['payment_slip']['name'];
                $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
                if (in_array($file_ext, $allowed_exts)) {
                    $upload_dir = 'uploads/slips/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }

                    $slip_filename = 'slip_' . $booking_id . '_' . time() . '.' . $file_ext;
                    $target_file   = $upload_dir . $slip_filename;

                    if (!move_uploaded_file($file_tmp, $target_file)) {
                        $slip_filename = null;
                    }
                }
            }

            // 2. คำนวณยอดบริการและส่วนต่างหลังหักคูปองที่บันทึกไว้แล้ว
            $stmt_price = $pdo->prepare("
                SELECT
                    COALESCE(SUM(srv.price), 0) as total_amount,
                    MAX(srv.id) as service_id,
                    MAX(srv.coupon_id) as service_coupon_id
                FROM bookings b
                LEFT JOIN booking_items bi ON b.id = bi.booking_id
                LEFT JOIN allservices srv ON bi.service_id = srv.id
                WHERE b.id = :b_id
                GROUP BY b.id
            ");
            $stmt_price->execute([':b_id' => $booking_id]);
            $price_data = $stmt_price->fetch(PDO::FETCH_ASSOC);
            $total_amount = $price_data['total_amount'] ?? 0;

            $stmt_coupon_total = $pdo->prepare("
                SELECT payment_id, amount, coupon_code
                FROM payments
                WHERE booking_id = :b_id
                    AND LOWER(TRIM(COALESCE(payment_method, ''))) IN ('coupon', 'คูปอง')
                ORDER BY payment_id ASC
            ");
            $stmt_coupon_total->execute([':b_id' => $booking_id]);
            $coupon_records = [];
            foreach ($stmt_coupon_total->fetchAll(PDO::FETCH_ASSOC) as $coupon_record) {
                $coupon_code_value = trim($coupon_record['coupon_code'] ?? '');
                $amount = (float)$coupon_record['amount'];
                if ($amount <= 0 && $coupon_code_value !== '') {
                    $amount = 300;
                }
                $codes = array_values(array_unique(array_filter(array_map('trim', explode(',', $coupon_code_value)))));
                $coupon_records[] = [
                    'amount' => $amount,
                    'codes' => $codes,
                    'payment_id' => (int)$coupon_record['payment_id']
                ];
            }

            $unique_coupon_records = [];
            foreach ($coupon_records as $coupon_record) {
                $codes = $coupon_record['codes'];
                sort($codes);
                $signature = empty($codes)
                    ? 'payment-' . $coupon_record['payment_id']
                    : implode(',', $codes);
                if (
                    !isset($unique_coupon_records[$signature])
                    || $coupon_record['payment_id'] > $unique_coupon_records[$signature]['payment_id']
                ) {
                    $unique_coupon_records[$signature] = $coupon_record;
                }
            }

            $combined_coupon_codes = [];
            foreach ($unique_coupon_records as $coupon_record) {
                if (count($coupon_record['codes']) > 1) {
                    foreach ($coupon_record['codes'] as $code) {
                        $combined_coupon_codes[$code] = true;
                    }
                }
            }

            $coupon_total = 0;
            foreach ($unique_coupon_records as $coupon_record) {
                if (
                    count($coupon_record['codes']) === 1
                    && isset($combined_coupon_codes[$coupon_record['codes'][0]])
                ) {
                    continue;
                }
                $coupon_total += $coupon_record['amount'];
            }
            $has_face_massage_coupon = !empty($coupon_records)
                && (int)($price_data['service_id'] ?? 0) === 35
                && (int)($price_data['service_coupon_id'] ?? 0) === 3;
            if ($has_face_massage_coupon) {
                $coupon_total = (float)$total_amount;
            }
            $remaining_amount = max(0, (float)$total_amount - $coupon_total);

            // 3. เปลี่ยนยอดค้างชำระเป็นช่องทางที่รับเงินจริง โดยไม่บันทึกยอดบริการซ้ำ
            if ($payment_method === 'coupon' && $coupon_total > 0 && $remaining_amount > 0) {
                throw new Exception("คิวนี้ใช้คูปองแล้ว กรุณาเลือกช่องทางชำระส่วนต่าง");
            } elseif ($payment_method === 'coupon') {
                if ($coupon_total <= 0) {
                    $stmt_pay = $pdo->prepare("
                        INSERT INTO payments (booking_id, amount, payment_method, coupon_code, slip_image, paid_at, created_at)
                        VALUES (:b_id, :amount, :method, :coupon, :slip, NOW(), NOW())
                    ");
                    $stmt_pay->execute([
                        ':b_id'   => $booking_id,
                        ':amount' => $remaining_amount,
                        ':method' => $payment_method,
                        ':coupon' => $coupon_code,
                        ':slip'   => $slip_filename
                    ]);
                }
            } elseif ($remaining_amount > 0) {
                $stmt_pay = $pdo->prepare("
                    UPDATE payments
                    SET amount = :amount,
                        payment_method = :method,
                        coupon_code = NULL,
                        slip_image = :slip,
                        paid_at = NOW()
                    WHERE booking_id = :b_id
                        AND LOWER(TRIM(COALESCE(payment_method, ''))) = 'pending'
                    ORDER BY payment_id DESC
                    LIMIT 1
                ");
                $stmt_pay->execute([
                    ':b_id'   => $booking_id,
                    ':amount' => $remaining_amount,
                    ':method' => $payment_method,
                    ':slip'   => $slip_filename
                ]);

                if ($stmt_pay->rowCount() === 0) {
                    $stmt_pay = $pdo->prepare("
                        INSERT INTO payments (booking_id, amount, payment_method, coupon_code, slip_image, paid_at, created_at)
                        VALUES (:b_id, :amount, :method, NULL, :slip, NOW(), NOW())
                    ");
                    $stmt_pay->execute([
                        ':b_id'   => $booking_id,
                        ':amount' => $remaining_amount,
                        ':method' => $payment_method,
                        ':slip'   => $slip_filename
                    ]);
                }
            }

            // 4. อัปเดตสถานะเป็น completed และบันทึก payment_method ลงตาราง bookings (ไม่มี slip_image)
            $stmt_book = $pdo->prepare("
                UPDATE bookings 
                SET status = 'completed', 
                    payment_method = :method
                WHERE id = :b_id
            ");
            $stmt_book->execute([
                ':method' => $payment_method,
                ':b_id'   => $booking_id
            ]);

            $pdo->commit();
            header("Location: dashboard.php?tab=queue&status=payment_success");
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            die("เกิดข้อผิดพลาดในการบันทึกการชำระเงิน: " . $e->getMessage());
        }
    }
}
?>