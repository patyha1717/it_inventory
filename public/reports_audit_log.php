<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers/auth.php';
auth();

$pdo = db();

// Fetch users for filter dropdown
$users = $pdo->query("SELECT id, name FROM users ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch unique action types from audit logs
$actions = $pdo->query("SELECT DISTINCT action FROM audit_logs ORDER BY action ASC")->fetchAll(PDO::FETCH_ASSOC);

$where = "1=1";
$params = [];

// Filters
if (!empty($_GET['user_id'])) {
    $where .= " AND a.user_id = ?";
    $params[] = $_GET['user_id'];
}
if (!empty($_GET['action'])) {
    $where .= " AND a.action = ?";
    $params[] = $_GET['action'];
}
if (!empty($_GET['from']) && !empty($_GET['to'])) {
    $where .= " AND DATE(a.created_at) BETWEEN ? AND ?";
    $params[] = $_GET['from'];
    $params[] = $_GET['to'];
}

// Main query
$sql = "
SELECT 
    a.*, 
    u.name AS username
FROM audit_logs a
LEFT JOIN users u ON a.user_id = u.id
WHERE $where
ORDER BY a.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/header.php';
?>

<style>
.table thead th {
    background: #1e293b !important;
    color: #fff !important;
}
</style>

<div class="container mt-4">

    <h3 class="mb-3">Full Audit Log Report</h3>

    <form method="GET" class="card p-3 mb-4">

        <div class="row">

            <div class="col-md-3">
                <label>User</label>
                <select name="user_id" class="form-control">
                    <option value="">All Users</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>" 
                            <?= isset($_GET['user_id']) && $_GET['user_id']==$u['id'] ? 'selected' : '' ?>>
                            <?= $u['name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label>Action</label>
                <select name="action" class="form-control">
                    <option value="">All Actions</option>
                    <?php foreach ($actions as $a): ?>
                        <option value="<?= $a['action'] ?>" 
                            <?= isset($_GET['action']) && $_GET['action']==$a['action'] ? 'selected' : '' ?>>
                            <?= ucfirst($a['action']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label>From Date</label>
                <input type="date" name="from" class="form-control" value="<?= $_GET['from'] ?? '' ?>">
            </div>

            <div class="col-md-2">
                <label>To Date</label>
                <input type="date" name="to" class="form-control" value="<?= $_GET['to'] ?? '' ?>">
            </div>

            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100">Filter</button>
            </div>

        </div>

    </form>

    <div class="card p-3">
        <table id="auditTable" class="table table-bordered table-striped">

            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Description</th>
                    <th>IP Address</th>
                    <th>Date & Time</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($logs as $l): ?>
                <tr>
                    <td><?= $l['id'] ?></td>
                    <td><?= htmlspecialchars($l['username']) ?></td>
                    <td><?= htmlspecialchars($l['action']) ?></td>
                    <td><?= htmlspecialchars($l['description']) ?></td>
                    <td><?= $l['ip_address'] ?></td>
                    <td><?= $l['created_at'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>

        </table>
    </div>

</div>

<!-- Datatable assets -->
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
    $('#auditTable').DataTable({
        dom: 'Bfrtip',
        order: [[0, "desc"]],
        buttons: [
            { extend: 'excel', className: 'btn btn-success btn-sm' },
            { extend: 'pdf', className: 'btn btn-danger btn-sm' },
            { extend: 'print', className: 'btn btn-info btn-sm' }
        ]
    });
});
</script>

<?php include __DIR__ . '/footer.php'; ?>
