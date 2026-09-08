<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/helpers/auth.php';

auth();
$pdo = db();

if ($_SESSION['role'] !== 'superadmin') {
    echo json_encode(["error" => "denied"]); 
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$id = (int)$data['id'];

if ($id == $_SESSION['user_id']) {
    echo json_encode(["error" => "cannot_delete_self"]);
    exit;
}

// Fetch user role
$stmt = $pdo->prepare("SELECT role FROM users WHERE id=?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(["error" => "notfound"]);
    exit;
}

if ($user['role'] === 'superadmin') {
    echo json_encode(["error" => "cannot_delete_superadmin"]);
    exit;
}

$stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
$stmt->execute([$id]);

echo json_encode(["success" => true]);
