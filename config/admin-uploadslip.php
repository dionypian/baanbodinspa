<?php
require_once 'db-connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = $_POST['booking_id'] ?? null;

    if (!empty($booking_id) && isset($_FILES['payment_slip']) && $_FILES['payment_slip']['error'] === UPLOAD_ERR_OK) {
        try {
            $pdo->beginTransaction();

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

                if (move_uploaded_file($file_tmp, $target_file)) {
                    // อัปเดตสลิปลงตาราง payments อย่างเดียว
                    $stmt_p = $pdo->prepare("UPDATE payments SET slip_image = :slip WHERE booking_id = :b_id ORDER BY payment_id DESC LIMIT 1");
                    $stmt_p->execute([':slip' => $slip_filename, ':b_id' => $booking_id]);

                    $pdo->commit();
                    header("Location: dashboard.php?tab=queue&status=slip_uploaded");
                    exit();
                }
            }

            throw new Exception("รูปแบบไฟล์ไม่ถูกต้อง หรืออัปโหลดไฟล์ไม่สำเร็จ");

        } catch (Exception $e) {
            $pdo->rollBack();
            die("เกิดข้อผิดพลาดในการอัปโหลดสลิป: " . $e->getMessage());
        }
    } else {
        header("Location: dashboard.php?tab=queue&status=error");
        exit();
    }
}
?>