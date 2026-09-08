<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
check_login();

$pdo = db();

$stmt = $pdo->query("SELECT id, name, asset_prefix, next_number FROM inv_categories ORDER BY id DESC");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($rows);
?>
