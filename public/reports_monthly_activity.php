<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers/auth.php';
auth();

$pdo = db();

// Fetch last 12 month stats
$sql = "
SELECT 
    DATE_FORMAT(a.assigned_on, '%Y-%m') AS month,
    COUNT(a.id) AS assigned
FROM inventory_assignments a
WHERE a.unassigned_on IS NULL
GROUP BY DATE_FORMAT(a.assigned_on, '%Y-%m')
ORDER BY month DESC
LIMIT 12
";
$assigned = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Unassigned items (returned)
$sql2 = "
SELECT 
    DATE_FORMAT(a.unassigned_on, '%Y-%m') AS month,
    COUNT(a.id) AS unassigned
FROM inventory_assignments a
WHERE a.unassigned_on IS NOT NULL
GROUP BY DATE_FORMAT(a.unassigned_on, '%Y-%m')
ORDER BY month DESC
LIMIT 12
";
$unassigned = $pdo->query($sql2)->fetchAll(PDO::FETCH_ASSOC);

// New inventory added
$sql3 = "
SELECT 
    DATE_FORMAT(i.created_at, '%Y-%m') AS month,
    COUNT(i.id) AS new_items
FROM inventory_items i
GROUP BY DATE_FORMAT(i.created_at, '%Y-%m')
ORDER BY month DESC
LIMIT 12
";
$newItems = $pdo->query($sql3)->fetchAll(PDO::FETCH_ASSOC);

// Users created
$sql4 = "
SELECT 
    DATE_FORMAT(u.created_at, '%Y-%m') AS month,
    COUNT(u.id) AS new_users
FROM users u
GROUP BY DATE_FORMAT(u.created_at, '%Y-%m')
ORDER BY month DESC
LIMIT 12
";
$newUsers = $pdo->query($sql4)->fetchAll(PDO::FETCH_ASSOC);

// Convert to structured array (month => ...)
$data = [];
foreach ($assigned as $x) {
    $data[$x['month']]['assigned'] = $x['assigned'];
}
foreach ($unassigned as $x) {
    $data[$x['month']]['unassigned'] = $x['unassigned'];
}
foreach ($newItems as $x) {
    $data[$x['month']]['new_items'] = $x['new_items'];
}
foreach ($newUsers as $x) {
    $data[$x['month']]['new_users'] = $x['new_users'];
}

// Fill missing values
foreach ($data as $m => $d) {
    $data[$m]['assigned'] = $data[$m]['assigned'] ?? 0;
    $data[$m]['unassigned'] = $data[$m]['unassigned'] ?? 0;
    $data[$m]['new_items'] = $data[$m]['new_items'] ?? 0;
    $data[$m]['new_users'] = $data[$m]['new_users'] ?? 0;
}

ksort($data);

include __DIR__ . '/header.php';
?>

<style>
.table thead th {
    background:#1e293b !important;
    color:white !important;
}
</style>

<div class="main-content-inner px-4 py-3 d-block w-100">

    <div class="container-fluid">
<div class="container">

    <h3>Monthly Activity Report</h3>
    <p class="text-muted">Last 12 Months Activity</p>

    <div class="card p-4 mb-4">
        <canvas id="activityChart" height="120"></canvas>
    </div>

    <table id="monthlyTable" class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Month</th>
                <th>Assigned</th>
                <th>Unassigned</th>
                <th>New Items</th>
                <th>New Users</th>
            </tr>
        </thead>
        <tbody>

        <?php foreach ($data as $month => $d): ?>
            <tr>
                <td><?= $month ?></td>
                <td><?= $d['assigned'] ?></td>
                <td><?= $d['unassigned'] ?></td>
                <td><?= $d['new_items'] ?></td>
                <td><?= $d['new_users'] ?></td>
            </tr>
        <?php endforeach; ?>

        </tbody>
    </table>

</div>
        </div>
        </div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const labels = <?= json_encode(array_keys($data)) ?>;
const assignedData = <?= json_encode(array_column($data, 'assigned')) ?>;
const unassignedData = <?= json_encode(array_column($data, 'unassigned')) ?>;
const newItemsData = <?= json_encode(array_column($data, 'new_items')) ?>;
const newUsersData = <?= json_encode(array_column($data, 'new_users')) ?>;

new Chart(document.getElementById('activityChart'), {
    type: 'line',
    data: {
        labels: labels,
        datasets: [
            {
                label: 'Assigned Items',
                data: assignedData,
                borderColor: '#2563eb',
                borderWidth: 2
            },
            {
                label: 'Unassigned Items',
                data: unassignedData,
                borderColor: '#ef4444',
                borderWidth: 2
            },
            {
                label: 'New Items Added',
                data: newItemsData,
                borderColor: '#22c55e',
                borderWidth: 2
            },
            {
                label: 'New Users',
                data: newUsersData,
                borderColor: '#eab308',
                borderWidth: 2
            }
        ]
    }
});
</script>

<script>
$(document).ready(function () {
    $('#monthlyTable').DataTable({
        dom: 'Bfrtip',
        buttons: [
            { extend: 'excel', className: 'btn btn-success btn-sm' },
            { extend: 'pdf', className: 'btn btn-danger btn-sm' },
            { extend: 'print', className: 'btn btn-info btn-sm' }
        ]
    });
});
</script>

<?php include __DIR__ . '/footer.php'; ?>
