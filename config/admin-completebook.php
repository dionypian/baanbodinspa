<?php
require_once 'db-connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id     = $_POST['booking_id'] ?? null;
    $payment_method = $_POST['payment_method'] ?? 'cash';
    $coupon_code    = $_POST['coupon_code'] ?? null;

    if (!empty($booking_id)) {
        try {
            $pdo->beginTransaction();

            // 1. คำนวณยอดเงินรวมของการจองนี้จากตาราง allservices ผ่าน booking_items
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

            // 2. บันทึกประวัติการชำระเงินลงตาราง payments
            $stmt_pay = $pdo->prepare("
                INSERT INTO payments (booking_id, amount, payment_method, coupon_code, paid_at, created_at)
                VALUES (:b_id, :amount, :method, :coupon, NOW(), NOW())
            ");
            $stmt_pay->execute([
                ':b_id'   => $booking_id,   
                ':amount' => $total_amount,
                ':method' => $payment_method,
                ':coupon' => $coupon_code
            ]);

            // 3. อัปเดตสถานะเป็น completed และบันทึก payment_method ลงตาราง bookings Direct
            $stmt_book = $pdo->prepare("
                UPDATE bookings 
                SET status = 'completed', payment_method = :method 
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