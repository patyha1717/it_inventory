<?php
// DEBUG ENABLED
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers/auth.php';

auth();

if (!is_superadmin()) {
    die("Access Denied");
}

$pdo = db();

$logs = $pdo->query("
    SELECT l.*, u.name, u.email
    FROM user_activity_logs l
    LEFT JOIN users u ON u.id = l.user_id
    ORDER BY l.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/header.php';
?>

<div class="container mt-4">
<h3>User Activity Log</h3>

<table class="table table-bordered table-striped">
<thead class="table-dark">
<tr>
    <th>User</th>
    <th>Action</th>
    <th>Description</th>
    <th>IP</th>
    <th>Date</th>
</tr>
</thead>
<tbody>
<?php foreach ($logs as $l): ?>
<tr>
    <td><?= htmlspecialchars($l['name']) ?> (<?= $l['email'] ?>)</td>
    <td><?= $l['action'] ?></td>
    <td><?= $l['description'] ?></td>
    <td><?= $l['ip_address'] ?></td>
    <td><?= $l['created_at'] ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
