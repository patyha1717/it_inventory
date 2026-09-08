<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers/auth.php';

auth(); // user must be logged in

// Only admin or superadmin can edit users
if (!in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header("Location: users.php?error=denied");
    exit;
}

// Get user ID
if (!isset($_GET['id'])) {
    header("Location: users.php?error=invalid");
    exit;
}

$id = (int)$_GET['id'];

$pdo = db();

// Fetch user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: users.php?error=notfound");
    exit;
}

// Prevent admin from editing superadmin
if ($_SESSION['role'] !== 'superadmin' && $user['role'] === 'superadmin') {
    header("Location: users.php?error=denied");
    exit;
}

$error = "";
$success = "";

// Save edits
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $employee_id = trim($_POST['employee_id']);
    $name        = trim($_POST['name']);
    $email       = trim($_POST['email']);
    $mobile      = trim($_POST['mobile']);
    $department  = trim($_POST['department']);
    $role        = trim($_POST['role']);
    $status      = isset($_POST['status']) ? 1 : 0;
    $resetPass   = trim($_POST['reset_password']);

    // Basic validation
    if ($name === "") {
        $error = "Name is required";
    }

    // Email unique check (allow same email for same user)
    if ($email !== "") {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email=? AND id!=?");
        $stmt->execute([$email, $id]);
        if ($stmt->fetch()) {
            $error = "Email already exists!";
        }
    }

    // If role != employee and password reset blank ? ok  
    // If role != employee and password void? ? no  
    if ($role !== "employee" && $user['role'] == "employee") {
        // converting employee ? login user
        if ($resetPass == "") {
            $error = "Password is required when converting Employee to Login User.";
        }
    }

    if ($error == "") {

        // Handle password update
        $hashed = $user['password'];
        if ($resetPass !== "") {
            $hashed = password_hash($resetPass, PASSWORD_BCRYPT);
        }

        // Prevent admin editing superadmin role
        if ($_SESSION['role'] !== 'superadmin') {
            $role = $user['role']; // lock role
        }

        // Update user
        $sql = "UPDATE users SET 
                    employee_id=?, 
                    name=?, 
                    email=?, 
                    mobile=?, 
                    department=?, 
                    role=?, 
                    password=?, 
                    status=?
                WHERE id=?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $employee_id,
            $name,
            $email,
            $mobile,
            $department,
            $role,
            $hashed,
            $status,
            $id
        ]);

        header("Location: users.php?updated=1");
        exit;
    }
}

include 'header.php';
?>

<div class="container mt-4">
    <h3>Edit User</h3>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">

        <div class="mb-3">
            <label class="form-label">Employee ID</label>
            <input type="text" name="employee_id" value="<?php echo htmlspecialchars($user['employee_id']); ?>" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Name <span class="text-danger">*</span></label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Email (Unique)</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Mobile</label>
            <input type="text" name="mobile" value="<?php echo htmlspecialchars($user['mobile']); ?>" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Department</label>
            <input type="text" name="department" value="<?php echo htmlspecialchars($user['department']); ?>" class="form-control">
        </div>

        <!-- ROLE -->
        <div class="mb-3">
    <label class="form-label">Role</label>
    
    <?php if ($_SESSION['role'] !== 'superadmin'): ?>
        <select class="form-control" disabled>
            <option value="employee" <?php if ($user['role']=='employee') echo 'selected'; ?>>Employee (No Login)</option>
            <option value="user" <?php if ($user['role']=='user') echo 'selected'; ?>>User</option>
            <option value="admin" <?php if ($user['role']=='admin') echo 'selected'; ?>>Admin</option>
            <option value="superadmin" <?php if ($user['role']=='superadmin') echo 'selected'; ?>>SuperAdmin</option>
        </select>
        <input type="hidden" name="role" value="<?php echo htmlspecialchars($user['role']); ?>">
        <small class="text-muted">Only SuperAdmin can change roles.</small>
    
    <?php else: ?>
        <select name="role" id="roleSelect" class="form-control">
            <option value="employee" <?php if ($user['role']=='employee') echo 'selected'; ?>>Employee (No Login)</option>
            <option value="user" <?php if ($user['role']=='user') echo 'selected'; ?>>User</option>
            <option value="admin" <?php if ($user['role']=='admin') echo 'selected'; ?>>Admin</option>
            <option value="superadmin" <?php if ($user['role']=='superadmin') echo 'selected'; ?>>SuperAdmin</option>
        </select>
    <?php endif; ?>
</div>
        <!-- PASSWORD RESET FIELD -->
        <div class="mb-3" id="passwordWrapper">
            <label class="form-label">Reset Password</label>
            <input type="text" name="reset_password" class="form-control" placeholder="Leave blank to keep existing">
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" name="status" class="form-check-input" <?php echo ($user['status']==1 ? 'checked' : ''); ?>>
            <label class="form-check-label">Active</label>
        </div>

        <button type="submit" class="btn btn-primary">Update User</button>
        <a href="users.php" class="btn btn-secondary">Back</a>

    </form>
</div>

<script>
// Hide reset password field if employee
function togglePasswordField() {
    const role = document.getElementById("roleSelect").value;
    const wrapper = document.getElementById("passwordWrapper");

    if (role === "employee") {
        wrapper.style.display = "none";
    } else {
        wrapper.style.display = "block";
    }
}

togglePasswordField();
document.getElementById("roleSelect").addEventListener("change", togglePasswordField);
</script>

<?php include 'footer.php'; ?>
