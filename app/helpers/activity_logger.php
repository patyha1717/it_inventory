<?php
require_once __DIR__ . '/../db.php';

function logActivity($action, $details = null) {

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION['user_id'])) {
        return; // no guest logging
    }

    $pdo = db();

    $stmt = $pdo->prepare("
        INSERT INTO user_activity_log
        (user_id, action, details, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $_SESSION['user_id'],
        $action,
        $details,
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
}
