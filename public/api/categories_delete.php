<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
check_login();
header("Content-Type: application/json");

$pdo = db();
$id = intval($_POST['id'] ?? 0);

if ($_SESSION['role'] !== 'superadmin') {
    echo json_encode(["status" => "error", "message" => "SuperAdmin role required to delete categories."]);
    exit;
}

if ($id <= 0) {
    echo json_encode(["status" => "error", "message" => "Invalid ID"]);
    exit;
}

// BLOCK delete if items exist
$chk = $pdo->prepare("SELECT COUNT(*) FROM inventory_items WHERE category_id = ?");
$chk->execute([$id]);
$count = $chk->fetchColumn();

if ($count > 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Cannot delete. Items exist under this category."
    ]);
    exit;
}

// Delete category
$stmt = $pdo->prepare("DELETE FROM inv_categories WHERE id = ?");
$stmt->execute([$id]);

echo json_encode(["status" => "success"]);
?>
