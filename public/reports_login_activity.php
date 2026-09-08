<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers/auth.php';
auth();

$pdo = db();

if (!is_superadmin()) {
    die("Access denied");
}


// Fetch users for dropdown
$users = $pdo->query("SELECT id, name FROM users ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

$where = "1=1";
$params = [];

// Filters
if (!empty($_GET['user_id'])) {
    $where .= " AND l.user_id = ?";
    $params[] = $_GET['user_id'];
}

if (!empty($_GET['action'])) {
    $where .= " AND l.action = ?";
    $params[] = $_GET['action'];
}

if (!empty($_GET['from']) && !empty($_GET['to'])) {
    $where .= " AND DATE(l.created_at) BETWEEN ? AND ?";
    $params[] = $_GET['from'];
    $params[] = $_GET['to'];
}

// Fetch logs
$sql = "
SELECT 
    l.*, 
    u.name AS username
FROM login_logs l
LEFT JOIN users u ON l.user_id = u.id
WHERE $where
ORDER BY l.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/header.php';
?>

<style>
.table thead th {
    background: #1e293b !important;
    color: white !important;
}
</style>

<!-- <div class="container mt-4"> -->

<div class="main-content-inner px-4 py-3 d-block w-100">

    <div class="container-fluid">
    <h3 class="mb-3">Login Activity Report</h3>

    <form method="GET" class="card p-3 mb-4">

        <div class="row">

            <div class="col-md-3">
                <label>User</label>
                <select name="user_id" class="form-control">
                    <option value="">All Users</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>" 
                            <?= (($_GET['user_id'] ?? '') == $u['id']) ? 'selected' : '' ?>>
                            <?= $u['name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label>Action</label>
                <select name="action" class="form-control">
                    <option value="">All</option>
                    <option value="login" <?= (($_GET['action'] ?? '') == "login") ? "selected" : "" ?>>Login</option>
                    <option value="logout" <?= (($_GET['action'] ?? '') == "logout") ? "selected" : "" ?>>Logout</option>
                </select>
            </div>

            <div class="col-md-2">
                <label>From</label>
                <input type="date" name="from" class="form-control" value="<?= $_GET['from'] ?? '' ?>">
            </div>

            <div class="col-md-2">
                <label>To</label>
                <input type="date" name="to" class="form-control" value="<?= $_GET['to'] ?? '' ?>">
            </div>

            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100">Filter</button>
            </div>

        </div>

    </form>

    <div class="card p-3">
        <table id="loginTable" class="table table-bordered table-striped">

            <thead>
                <tr>
                    <th>SR#</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>IP Address</th>
                    <th>Device</th>
                    <th>Date & Time</th>
                </tr>
            </thead>

            <tbody>
                <?php 
    		$sr_no = 1; // 1. Initialize counter
    		foreach ($logs as $l): 
    		?>
    		<tr>
        	<td><?= $sr_no++ ?></td> <td><?= htmlspecialchars($l['username']) ?></td>
                    <td>
                        <?php if ($l['action'] == 'login'): ?>
                            <span class="badge bg-success">Login</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Logout</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $l['ip_address'] ?></td>
                    <td><?= htmlspecialchars(substr($l['user_agent'], 0, 40)) ?>...</td>
                    <td><?= $l['created_at'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>

        </table>
    </div>
     </div>

</div>

<!-- Datatable libraries -->
<link rel="stylesheet"
      href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
<link rel="stylesheet"
      href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css">

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script
src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script
src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script
src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script
src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>

<script>
$(document).ready(function () {
    $('#loginTable').DataTable({
        dom: 'Bfrtip',
        order: [[0, "desc"]],
        buttons: [
            { extend: 'excel', className: 'btn btn-success btn-sm' },
            { extend: 'pdf',   className: 'btn btn-danger btn-sm' },
            { extend: 'print', className: 'btn btn-info btn-sm' }
        ]
    });
});
</script>

<?php include __DIR__ . '/footer.php'; ?>
