<?php
$email = $_GET['email'] ?? '';
$msg = $_GET['msg'] ?? '';
?>

<!DOCTYPE html>
<html>
<head>
<title>Reset Password</title>
<link rel="stylesheet" href="/itms.arukustech.com/public/assets/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container" style="max-width:420px;margin-top:80px;">
<div class="card shadow">
<div class="card-body">

<h4 class="text-center">Reset Password</h4>

<?php if ($msg): ?>
<div class="alert alert-danger"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<form method="POST" action="/itms.arukustech.com/public/api/reset_password.php">
    <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">

    <div class="mb-3">
        <label>OTP</label>
        <input type="text" name="otp" required class="form-control">
    </div>

    <div class="mb-3">
        <label>New Password</label>
        <input type="password" name="password" required class="form-control">
    </div>

    <button class="btn btn-success w-100">
        Reset Password
    </button>
</form>

</div>
</div>
</div>

</body>
</html>
