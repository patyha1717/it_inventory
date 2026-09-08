<?php
require_once __DIR__ . '/../app/db.php';
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }

$pdo = db();

// Save logout log BEFORE session destroy
if (!empty($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("
        INSERT INTO login_logs (user_id, action, ip_address, user_agent) 
        VALUES (?, 'logout', ?, ?)
    ");

    $stmt->execute([
        $_SESSION['user_id'],
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
}

// Destroy session
session_unset();
session_destroy();

// Redirect
header("Location: /itms.arukustech.com/public/login.php");
exit;
