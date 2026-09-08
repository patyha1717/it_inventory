<?php
require_once __DIR__ . '/../app/helpers/auth.php';
auth();

// Only admin/superadmin can add users
if (!in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header("Location: users.php?error=denied");
    exit;
}

include __DIR__ . '/header.php';
?>

<div class="container mt-4">

    <h3>Add User</h3>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <form action="/itms.arukustech.com/public/api/users_add.php" method="POST">

        <div class="mb-3">
            <label class="form-label">Employee ID</label>
            <input type="text" name="employee_id" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Name *</label>
            <input type="text" name="name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Email (Unique)</label>
            <input type="email" name="email" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Mobile</label>
            <input type="text" name="mobile" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Department</label>
            <input type="text" name="department" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Role *</label>
            <select name="role" class="form-control" required onchange="togglePassword(this.value)">
                <option value="employee">Employee (No Login)</option>
                <option value="user">User (Login)</option>
                <option value="admin">Admin</option>
                <option value="superadmin">SuperAdmin</option>
            </select>
        </div>

        <div class="mb-3" id="passwordRow">
            <label class="form-label">Password (Only required for login users)</label>
            <input type="password" name="password" class="form-control">
        </div>

        <div class="mb-3 form-check">
            <input class="form-check-input" type="checkbox" name="status" checked>
            <label class="form-check-label">Active</label>
        </div>

        <button class="btn btn-primary">Save User</button>
        <a href="users.php" class="btn btn-secondary">Back</a>

    </form>

</div>

<script>
function togglePassword(role) {
    let row = document.getElementById("passwordRow");
    row.style.display = (role === "employee") ? "none" : "block";
}
togglePassword(document.querySelector("select[name='role']").value);
</script>

<?php include __DIR__ . '/footer.php'; ?>
