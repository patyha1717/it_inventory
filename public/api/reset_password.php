<?php
// DEBUG ENABLED
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../../app/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /itms.arukustech.com/public/login.php");
    exit;
}

$token    = $_POST['token'] ?? '';
$password = $_POST['password'] ?? '';

if ($token === '' || $password === '') {
   header("Location: /itms.arukustech.com/public/reset.php?token=$token&msg=Invalid request");  
    exit;
}

$pdo = db();

/* Validate token (15 min expiry) */
$st = $pdo->prepare("
    SELECT * FROM password_resets 
    WHERE token = ? 
    AND created_at >= NOW() - INTERVAL 15 MINUTE
");
$st->execute([$token]);
$row = $st->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    die("Invalid or expired reset link.");
}

/* Hash password */
$hashed = password_hash($password, PASSWORD_DEFAULT);

/* Update password */
$pdo->prepare("
    UPDATE users SET password=? WHERE email=?
")->execute([$hashed, $row['email']]);

/* Delete token */
$pdo->prepare("
    DELETE FROM password_resets WHERE token=?
")->execute([$token]);

header("Location: /itms.arukustech.com/public/login.php?msg=Password reset successful");
exit;
