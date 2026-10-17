<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION = array(); // ล้างข้อมูล session ทั้งหมด

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],$params["secure"], $params["httponly"]
    );
}

session_destroy();

header("Location: http://localhost/baanbodinspa/config/index.php");
exit();
?>