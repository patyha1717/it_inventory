<?php
require_once __DIR__ . '/../app/db.php';
$pdo = db();
$message = '';
$token = $_GET['token'] ?? '';

if (!$token) {
    die("Invalid access. No token provided.");
}

// Verify if token exists in the database
$stmt = $pdo->prepare("SELECT id, name FROM users WHERE reset_token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    die("This reset link is invalid or has already been used.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if (strlen($password) < 6) {
        $message = "<div class='alert alert-danger'>Password must be at least 6 characters.</div>";
    } elseif ($password !== $confirm) {
        $message = "<div class='alert alert-danger'>Passwords do not match.</div>";
    } else {
        // Hash the new password securely
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        // Update password and CLEAR the token so it cannot be used again
        $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL WHERE id = ?");
        $stmt->execute([$hashed, $user['id']]);

        $message = "<div class='alert alert-success'>Password updated successfully! <a href='login.php'>Login here</a></div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Set New Password</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light d-flex align-items-center" style="height: 100vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-4 card p-4 shadow-sm">
                <h4 class="text-center mb-3">Reset Password</h4>
                <p class="text-center small text-muted">Hello, <?= htmlspecialchars($user['name']) ?>. Enter your new password below.</p>
                <?= $message ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">New Password</label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required minlength="6">
                    </div>
                    <button type="submit" class="btn btn-success w-100 fw-bold">Update Password</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>