<?php
require_once __DIR__ . '/../db.php';

function log_action($user_id, $action) {
    $pdo = db();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    $st = $pdo->prepare("
        INSERT INTO audit_logs(user_id, action, ip_address)
        VALUES (?, ?, ?)
    ");
    $st->execute([$user_id, $action, $ip]);
}
