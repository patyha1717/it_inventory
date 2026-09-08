<?php
// DEBUG ENABLED
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Load dependencies and environment
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/env_loader.php';
require_once __DIR__ . '/../app/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $pdo = db();

    // 1. Verify if the user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // 2. Generate a secure token and expiry (valid for 1 hour)
        $token = bin2hex(random_bytes(32));
        $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

        // 3. Update database with token (Make sure columns exist in your users table)
        $update = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE email = ?");
        $update->execute([$token, $expiry, $email]);

        // 4. Construct the reset link
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
         
        $resetLink = $protocol . $_SERVER['HTTP_HOST'] . "/itms.arukustech.com/public/reset_password.php?token=" . $token;

        // 5. Send Email using your existing env variables
        $mail = new PHPMailer(true);

        try {
            // SMTP Config
            $mail->isSMTP();
            $mail->Host       = env('SMTP_HOST');
            $mail->SMTPAuth   = true;
            $mail->Username   = env('SMTP_USERNAME');
            $mail->Password   = env('SMTP_PASSWORD');
            $mail->Port       = env('SMTP_PORT');
            $mail->SMTPSecure = env('SMTP_SECURE');

            // Metadata
            $mail->setFrom(env('MAIL_FROM'), env('COMPANY_NAME'));
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = "Password Reset Request";
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; border: 1px solid #e2e8f0; padding: 20px; border-radius: 10px;'>
                    <h2 style='color: #1e293b;'>Password Reset Request</h2>
                    <p>Hello,</p>
                    <p>You requested to reset your password. Please click the button below to set a new password. This link will expire in 1 hour.</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='$resetLink' style='background: #2563eb; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Reset My Password</a>
                    </div>
                    <p style='color: #64748b; font-size: 0.85rem;'>If you did not request this, please ignore this email.</p>
                </div>";

            $mail->send();
            $message = "<div class='alert alert-success'>A reset link has been sent to your email address.</div>";
        } catch (Exception $e) {
            $message = "<div class='alert alert-danger'>Email could not be sent. Mailer Error: {$mail->ErrorInfo}</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>No account found with that email address.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        body { background: #f1f5f9; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .forgot-card { width: 100%; max-width: 400px; background: #fff; padding: 30px; border-radius: 15px; border: 1px solid #e2e8f0; }
        .brand-icon { font-size: 2.5rem; color: #2563eb; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="forgot-card shadow-sm text-center">
    <div class="brand-icon"><i class="ri-lock-password-line"></i></div>
    <h4 class="fw-bold">Forgot Password</h4>
    <p class="text-muted mb-4 small">Enter your registered email to receive a reset link.</p>

    <?= $message ?>

    <form method="POST" class="text-start">
        <div class="mb-3">
            <label class="form-label fw-bold small">Email Address</label>
            <input type="email" name="email" class="form-control" placeholder="name@company.com" required>
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">Send Reset Link</button>
    </form>

    <div class="mt-4">
        <a href="login.php" class="text-decoration-none small text-primary"><i class="ri-arrow-left-line"></i> Back to Login</a>
    </div>
</div>

</body>
</html>