<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/helpers/auth.php';

auth();

// Only admin/superadmin can add users
if (!in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header("Location: /users.php?error=denied");
    exit;
}

$pdo = db();

// Validate required fields
$name  = trim($_POST['name']);
$role  = trim($_POST['role']);
$email = trim($_POST['email']);
$empid = trim($_POST['employee_id']);
$mobile = trim($_POST['mobile']);
$dept   = trim($_POST['department']);
$status = isset($_POST['status']) ? 1 : 0;
$password = trim($_POST['password']);

if ($name === "") {
    header("Location: /users_add.php?error=Name is required");
    exit;
}

// Password only needed for login users
if ($role !== "employee") {
    if ($password === "") {
        header("Location: /users_add.php?error=Password is required for login users");
        exit;
    }
    $hashed = password_hash($password, PASSWORD_BCRYPT);
} else {
    // Dummy password because DB requires NOT NULL
    $hashed = password_hash("employee123", PASSWORD_BCRYPT);
}

// Unique email check
if ($email !== "") {
    $check = $pdo->prepare("SELECT id FROM users WHERE email=?");
    $check->execute([$email]);

    if ($check->fetch()) {
        header("Location: /users_add.php?error=Email already exists");
        exit;
    }
}

$stmt = $pdo->prepare("
    INSERT INTO users (employee_id, name, email, mobile, department, role, password, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    $empid,
    $name,
    $email,
    $mobile,
    $dept,
    $role,
    $hashed,
    $status
]);

header("Location: /users.php?added=1");
exit;
