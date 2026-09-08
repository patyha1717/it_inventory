<?php
function log_action($action, $description = '') {
    if (empty($_SESSION['user_id'])) return;

    require __DIR__ . '/../db.php';
    $pdo = db();

    $stmt = $pdo->prepare("
        INSERT INTO user_activity_logs
        (user_id, action, description, ip_address)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([
        $_SESSION['user_id'],
        $action,
        $description,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
}
