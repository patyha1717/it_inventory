<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
check_login();

header("Content-Type: application/json");

// SECURITY: Verify user is SuperAdmin before allowing any changes
if ($_SESSION['role'] !== 'superadmin') {
    echo json_encode(["status" => "error", "message" => "Access denied. Only SuperAdmins can update category settings."]);
    exit;
}

$id = $_POST['id'] ?? null;
$name = trim($_POST['name'] ?? "");
$prefix = strtoupper(trim($_POST['asset_prefix'] ?? ""));
$next_number = intval($_POST['next_number'] ?? 0); 

if (!$id || $name == "" || $prefix == "" || $next_number <= 0) {
    echo json_encode(["status" => "error", "message" => "Missing or invalid fields"]);
    exit;
}

try {
    $pdo = db();

    // Updated SQL to include next_number
    $stmt = $pdo->prepare("
        UPDATE inv_categories 
        SET name = ?, asset_prefix = ?, next_number = ?
        WHERE id = ?
    ");

    $stmt->execute([$name, $prefix, $next_number, $id]);

    echo json_encode(["status" => "success"]);
} 
catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>