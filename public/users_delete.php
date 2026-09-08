<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers/auth.php';

auth(); // login required

$pdo = db();

// Only superadmin can delete users
if ($_SESSION['role'] !== 'superadmin') {
    header("Location: users.php?error=denied");
    exit;
}

// Validate ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: users.php?error=invalid");
    exit;
}

$id = (int)$_GET['id'];

// Prevent deleting own account
if ($id == $_SESSION['user_id']) {
    header("Location: users.php?error=cannot_delete_self");
    exit;
}

// Fetch user to check role
$stmt = $pdo->prepare("SELECT id, role FROM users WHERE id=?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: users.php?error=notfound");
    exit;
}

// Prevent deleting another superadmin
if ($user['role'] === 'superadmin') {
    header("Location: users.php?error=cannot_delete_superadmin");
    exit;
}

// Delete user
$stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
$stmt->execute([$id]);

header("Location: users.php?deleted=1");
exit;
