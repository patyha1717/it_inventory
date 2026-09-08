<?php
// DEBUG ENABLED
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
check_login();
header("Content-Type: application/json");

$pdo = db();

$name = trim($_POST['name'] ?? '');
$prefix = strtoupper(trim($_POST['asset_prefix'] ?? ''));

if ($name === "" || $prefix === "") {
    echo json_encode(["status" => "error", "message" => "Name & Prefix required"]);
    exit;
}

// Check duplicate prefix
$check = $pdo->prepare("SELECT id FROM inv_categories WHERE asset_prefix = ?");
$check->execute([$prefix]);

if ($check->fetch()) {
    echo json_encode(["status" => "error", "message" => "Prefix already exists"]);
    exit;
}

// Insert new category
$stmt = $pdo->prepare("
    INSERT INTO inv_categories (name, asset_prefix, next_number)
    VALUES (?, ?, 1)
");

if ($stmt->execute([$name, $prefix])) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => "Database insert failed"]);
}
?>
