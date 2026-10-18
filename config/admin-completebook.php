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

            // 2. คำนวณยอดเงินรวมของการจองนี้
            $stmt_price = $pdo->prepare("
                SELECT COALESCE(SUM(srv.price), 0) as total_amount
                FROM bookings b
                LEFT JOIN booking_items bi ON b.id = bi.booking_id
                LEFT JOIN allservices srv ON bi.service_id = srv.id
                WHERE b.id = :b_id
                GROUP BY b.id
            ");
            $stmt_price->execute([':b_id' => $booking_id]);
            $price_data = $stmt_price->fetch(PDO::FETCH_ASSOC);
            $total_amount = $price_data['total_amount'] ?? 0;

            // 3. บันทึกประวัติการชำระเงินลงตาราง payments เท่านั้น (รวม slip_image)
            $stmt_pay = $pdo->prepare("
                INSERT INTO payments (booking_id, amount, payment_method, coupon_code, slip_image, paid_at, created_at)
                VALUES (:b_id, :amount, :method, :coupon, :slip, NOW(), NOW())
            ");
            $stmt_pay->execute([
                ':b_id'   => $booking_id,   
                ':amount' => $total_amount,
                ':method' => $payment_method,
                ':coupon' => $coupon_code,
                ':slip'   => $slip_filename
            ]);

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