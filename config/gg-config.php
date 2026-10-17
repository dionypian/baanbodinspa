<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ถอยออกไปหาโฟลเดอร์ vendor หลัก
require_once __DIR__ . '/../vendor/autoload.php';

define('GOOGLE_CLIENT_ID', '419273353381-4ltmpl7bhi9s7e78acklbtflfiu0bgt3.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');
define('GOOGLE_REDIRECT_URI', 'http://localhost/baanbodinspa/config/gg-callback.php');

$googleClient = new Google\Client();
$googleClient->setClientId(GOOGLE_CLIENT_ID);
$googleClient->setClientSecret(GOOGLE_CLIENT_SECRET);
$googleClient->setRedirectUri(GOOGLE_REDIRECT_URI);
$googleClient->addScope('email');
$googleClient->addScope('profile');
?>