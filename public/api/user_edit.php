<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/helpers/auth.php';

auth();
$pdo = db();

$data = json_decode(file_get_contents("php://input"), true);
$id = (int)$data['id'];

if (!in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    echo json_encode(["error" => "denied"]); exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["error" => "notfound"]); exit;
}

$employee_id = trim($data['employee_id'] ?? $user['employee_id']);
$name        = trim($data['name'] ?? $user['name']);
$email       = trim($data['email'] ?? $user['email']);
$mobile      = trim($data['mobile'] ?? $user['mobile']);
$department  = trim($data['department'] ?? $user['department']);
$role        = trim($data['role'] ?? $user['role']);
$status      = $data['status'] ?? $user['status'];

$resetPass = trim($data['password'] ?? "");

if ($resetPass != "") {
    $hashed = password_hash($resetPass, PASSWORD_BCRYPT);
} else {
    $hashed = $user['password'];
}

$sql = "UPDATE users SET employee_id=?, name=?, email=?, mobile=?, department=?, role=?, password=?, status=? WHERE id=?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$employee_id, $name, $email, $mobile, $department, $role, $hashed, $status, $id]);

echo json_encode(["success" => true]);
