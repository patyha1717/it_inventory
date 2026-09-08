<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers/auth.php';
check_login();

$pdo = db();

/* =======================================================
   FETCH FILTER OPTIONS
======================================================= */
$items = $pdo->query("
    SELECT DISTINCT i.id, i.item_details, i.asset_code
    FROM inventory_items i
    JOIN audit_log a ON a.asset_id = i.id
    ORDER BY i.item_details ASC
")->fetchAll(PDO::FETCH_ASSOC);

$users = $pdo->query("
    SELECT DISTINCT u.id, u.name 
    FROM users u 
    JOIN audit_log a ON a.user_id = u.id
    ORDER BY u.name ASC
")->fetchAll(PDO::FETCH_ASSOC);

/* =======================================================
   FILTER VALUES
======================================================= */
$item_id   = $_GET['item_id']   ?? "";
$user_id   = $_GET['user_id']   ?? "";
$action    = $_GET['action']    ?? "";
$date_from = $_GET['from']      ?? "";
$date_to   = $_GET['to']        ?? "";

/* =======================================================
   DYNAMIC QUERY (WITH LOCATION)
======================================================= */
$sql = "
SELECT 
    a.*, 
    u.name AS username,
    i.item_details,
    i.asset_code,
    ia.city,
    ia.state
FROM audit_log a
LEFT JOIN users u ON u.id = a.user_id
LEFT JOIN inventory_items i ON i.id = a.asset_id

-- LOCATION SOURCE
LEFT JOIN inventory_assignments ia 
       ON ia.item_id = a.asset_id 
      AND ia.unassigned_on IS NULL

WHERE 1
";

$params = [];

if ($item_id != "") { $sql .= " AND a.asset_id = ?"; $params[] = $item_id; }
if ($user_id != "") { $sql .= " AND a.user_id = ?";  $params[] = $user_id; }
if ($action  != "") { $sql .= " AND a.action = ?";   $params[] = $action; }

if ($date_from != "") { $sql .= " AND DATE(a.created_at) >= ?"; $params[] = $date_from; }
if ($date_to   != "") { $sql .= " AND DATE(a.created_at) <= ?"; $params[] = $date_to; }

$sql .= " ORDER BY a.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =======================================================
   EXPORT CSV (WITH LOCATION)
======================================================= */
if (isset($_GET['export']) && $_GET['export'] == "csv") {

    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=audit_log.csv");

    $f = fopen("php://output", "w");
    fputcsv($f, [
        "ID",
        "User",
        "Item",
        "Asset Code",
        "Action",
        "City",
        "State",
        "Description",
        "Date"
    ]);

    foreach ($logs as $r) {
        fputcsv($f, [
            $r['id'],
            $r['username'],
            $r['item_details'],
            $r['asset_code'],
            $r['action'],
            $r['city'] ?? '',
            $r['state'] ?? '',
            $r['description'],
            $r['created_at']
        ]);
    }

    fclose($f);
    exit;
}

include __DIR__ . '/header.php';
?>

<div class="main-content-inner px-4 py-3 d-block w-100">
<div class="container-fluid">

<h3 class="fw-bold">Audit Log Report</h3>

<!-- ======================= FILTER BOX ======================= -->
<form method="GET" class="card p-3 mb-4">
    <div class="row g-3">

        <div class="col-md-3">
            <label>Asset</label>
            <select name="item_id" class="form-select">
                <option value="">All</option>
                <?php foreach ($items as $i): ?>
                    <option value="<?= $i['id'] ?>" <?= $item_id == $i['id'] ? "selected" : "" ?>>
                        <?= $i['item_details'] ?> (<?= $i['asset_code'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3">
            <label>User</label>
            <select name="user_id" class="form-select">
                <option value="">All</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= $user_id == $u['id'] ? "selected" : "" ?>>
                        <?= $u['name'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label>Action</label>
            <select name="action" class="form-select">
                <option value="">All</option>
                <option value="assign" <?= $action=='assign' ? "selected" : "" ?>>Assign</option>
                <option value="unassign" <?= $action=='unassign' ? "selected" : "" ?>>Unassign</option>
            </select>
        </div>

        <div class="col-md-2">
            <label>From Date</label>
            <input type="date" name="from" value="<?= $date_from ?>" class="form-control">
        </div>

        <div class="col-md-2">
            <label>To Date</label>
            <input type="date" name="to" value="<?= $date_to ?>" class="form-control">
        </div>

    </div>

    <div class="mt-3">
        <button class="btn btn-dark">Filter</button>
        <a href="reports_audit.php" class="btn btn-secondary ms-2">Reset</a>
        <a href="?<?= http_build_query($_GET) ?>&export=csv" class="btn btn-success ms-2">
            Export CSV
        </a>
    </div>
</form>

<!-- ======================= TABLE ======================= -->
<div class="card">
<div class="card-body p-0">

<table class="table table-bordered table-striped mb-0">
<thead class="table-dark">
<tr>
    <th>SR#</th>
    <th>User</th>
    <th>Item</th>
    <th>Asset Code</th>
    <th>Action</th>
    <th>Location</th>
    <th>Description</th>
    <th>Date</th>
</tr>
</thead>

<tbody>
<?php 
// Initialize the counter before the loop starts
$sr_no = 1; 
foreach ($logs as $row): 
?>
<tr>
    <td><?= $sr_no++ ?></td>
    <td><?= htmlspecialchars($row['username']) ?></td>
    <td><?= htmlspecialchars($row['item_details']) ?></td>
    <td><?= htmlspecialchars($row['asset_code']) ?></td>
    <td><?= ucfirst($row['action']) ?></td>

    <td>
        <?= htmlspecialchars($row['city'] ?? 'N/A') ?><br>
        <small class="text-muted">
            <?= htmlspecialchars($row['state'] ?? 'N/A') ?>
        </small>
    </td>

    <td><?= htmlspecialchars($row['description']) ?></td>
    <td><?= $row['created_at'] ?></td>
</tr>
<?php endforeach; ?>

<?php if (empty($logs)): ?>
<tr>
    <td colspan="8" class="text-center text-muted">
        No audit logs found.
    </td>
</tr>
<?php endif; ?>

</tbody>
</table>

</div>
</div>

</div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
