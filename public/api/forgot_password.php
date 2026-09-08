<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/mailer.php';

$secret = "6LfMHT8sAAAAAETLKx0nfpMnvYAK5xiKJHSQWO4P";

$email = trim($_POST['email'] ?? '');
$captcha = $_POST['g-recaptcha-response'] ?? '';

if (!$email || !$captcha) {
    header("Location: /forgot.php?msg=Invalid request");
    exit;
}

/* CAPTCHA VERIFY */
$verify = file_get_contents(
    "https://www.google.com/recaptcha/api/siteverify?secret=$secret&response=$captcha"
);
$resp = json_decode($verify, true);

if (!$resp['success']) {
    header("Location: /forgot.php?msg=Captcha verification failed");
    exit;
}

$pdo = db();

/* CHECK USER */
$st = $pdo->prepare("SELECT id FROM users WHERE email=? AND status=1");
$st->execute([$email]);
if (!$st->fetch()) {
    header("Location: /forgot.php?msg=Email not found");
    exit;
}

/* GENERATE OTP */
$otp = random_int(100000, 999999);
$otp_hash = password_hash($otp, PASSWORD_DEFAULT);
$expiry = date("Y-m-d H:i:s", strtotime("+10 minutes"));

$pdo->prepare("DELETE FROM password_otps WHERE email=?")->execute([$email]);

$pdo->prepare("
    INSERT INTO password_otps (email, otp_hash, expires_at)
    VALUES (?,?,?)
")->execute([$email, $otp_hash, $expiry]);

/* SEND EMAIL */
$subject = "Your Password Reset OTP";
$body = "
<p>Your OTP is:</p>
<h2>$otp</h2>
<p>Valid for <b>10 minutes</b>.</p>
<p>If you didn’t request this, ignore this email.</p>
";

if (!sendMail($email, $subject, $body)) {
    header("Location: /forgot.php?msg=Failed to send email");
    exit;
}

header("Location: /reset.php?email=" . urlencode($email));
exit;
