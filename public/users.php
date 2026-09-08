<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include __DIR__ . '/header.php';   // only once

require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers/auth.php';

auth(); 

$pdo = db();

// Pagination
$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Search
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

$where = "";
$params = [];

if ($search !== "") {
    $where = "WHERE name LIKE ? OR email LIKE ? OR employee_id LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%"];
}

// Total count
$totalSql = "SELECT COUNT(*) FROM users $where";
$stmt = $pdo->prepare($totalSql);
$stmt->execute($params);
$total = $stmt->fetchColumn();
$pages = ceil($total / $limit);

// Fetch users
$sql = "SELECT * FROM users $where ORDER BY id DESC LIMIT $start, $limit";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!-- <div class="container mt-4"> -->
    <div class="container-fluid mt-2">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>User Management</h3>

        <div>
            <a href="users_bulk_upload.php" class="btn btn-secondary btn-sm">Bulk Upload</a>
            <a href="users_add.php" class="btn btn-primary btn-sm">Add User</a>
        </div>
    </div>

    <!-- SUCCESS & ERROR ALERTS -->
    <?php if (isset($_GET['added'])): ?>
        <div class="alert alert-success">User added successfully.</div>
    <?php endif; ?>

    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success">User updated successfully.</div>
    <?php endif; ?>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">User deleted successfully.</div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">
            <?php
            $err = $_GET['error'];
            if ($err == "denied") echo "You do not have permission.";
            if ($err == "invalid") echo "Invalid request.";
            if ($err == "notfound") echo "User not found.";
            if ($err == "cannot_delete_self") echo "You cannot delete your own account.";
            if ($err == "cannot_delete_superadmin") echo "You cannot delete a SuperAdmin.";
            ?>
        </div>
    <?php endif; ?>

    <!-- <form method="GET" class="mb-3">
        <input 
            type="text" 
            name="search" 
            class="form-control" 
            placeholder="Search by name, email, employee ID..." 
            value="<?//php echo htmlspecialchars($search); ?>"
        >
    </form> -->

<form method="GET" class="mb-2">
    <input 
        type="search"
        name="search"
        class="form-control"
        placeholder="Search by name, email, employee ID…  ↵"
        value="<?php echo htmlspecialchars($search); ?>"
    >
    <small class="text-muted ms-1">
        Press <b>Enter</b> to search
    </small>
</form>



<div class="table-responsive">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>#</th>
                <th>Employee ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Mobile</th>
                <th>Department</th>
                <th>Role</th>
                <th>Status</th>
                <th width="140">Actions</th>
            </tr>
        </thead>

        <tbody>
            <?php if (empty($users)): ?>
                <tr>
                    <td colspan="9" class="text-center">No users found</td>
                </tr>
            <?php else: ?>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?php echo $u['id']; ?></td>
                        <td><?php echo htmlspecialchars($u['employee_id']); ?></td>
                        <td><?php echo htmlspecialchars($u['name']); ?></td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td><?php echo htmlspecialchars($u['mobile']); ?></td>
                        <td><?php echo htmlspecialchars($u['department']); ?></td>

                        <td>
                            <?php if ($u['role'] == 'employee'): ?>
                                <span class="badge bg-secondary">Employee</span>
                            <?php elseif ($u['role'] == 'admin'): ?>
                                <span class="badge bg-info">Admin</span>
                            <?php elseif ($u['role'] == 'superadmin'): ?>
                                <span class="badge bg-danger">SuperAdmin</span>
                            <?php else: ?>
                                <span class="badge bg-primary">User</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if ($u['status'] == 1): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactive</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <a href="users_edit.php?id=<?php echo $u['id']; ?>" 
                               class="btn btn-sm btn-warning">Edit</a>

                            <?php if ($_SESSION['role'] === 'superadmin' && $u['role'] !== 'superadmin'): ?>
                                <a href="users_delete.php?id=<?php echo $u['id']; ?>" 
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Delete this user?')">Delete</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    </div>

    <!-- Pagination -->
    <nav>
        <ul class="pagination">
            <?php for ($i=1; $i <= $pages; $i++): ?>	
                <li class="page-item <?php echo ($i == $page ? 'active' : ''); ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>

</div>

<script>
// Auto-hide alerts after 3 seconds
setTimeout(() => {
    let alerts = document.querySelectorAll('.alert');
    alerts.forEach(a => a.style.display = 'none');
}, 3000);
</script>

<?php include 'footer.php'; ?>
