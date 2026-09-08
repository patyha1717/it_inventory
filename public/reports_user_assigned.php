<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers/auth.php';
auth();

$pdo = db();

/* Fetch User-wise Assigned Items */
$sql = "
    SELECT 
        u.id AS user_id,
        u.name AS user_name,
        u.email,
        COUNT(a.id) AS total_assigned
    FROM users u
    LEFT JOIN inventory_assignments a 
        ON u.id = a.user_id AND a.unassigned_on IS NULL
    WHERE u.status = 1
    GROUP BY u.id
    ORDER BY u.name ASC
";

$users = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

/* Fetch assigned details for each user */
$details = [];

$sql2 = "
    SELECT 
        a.user_id,
        i.item_details,
        i.brand,
        i.model,
        i.serial_no,
        i.category_id,
        c.name AS category_name,
        a.assigned_on
    FROM inventory_assignments a
    JOIN inventory_items i ON a.item_id = i.id
    LEFT JOIN inv_categories c ON i.category_id = c.id
    WHERE a.unassigned_on IS NULL
    ORDER BY a.assigned_on DESC
";

$data = $pdo->query($sql2)->fetchAll(PDO::FETCH_ASSOC);

foreach ($data as $d) {
    $details[$d['user_id']][] = $d;
}

include __DIR__ . '/header.php';
?>

<style>
.table thead th {
    background:#1e293b !important;
    color:white !important;
}
.details-box {
    background: #f8fafc;
    padding: 10px;
    border-radius: 6px;
    margin-top: 10px;
    border: 1px solid #e2e8f0;
}
</style>

<!-- <div class="container mt-4"> -->
    <div class="main-content-inner px-4 py-3">

    <h3>User Wise Assigned Assets Report</h3>

    <table id="userAssignTable" class="table table-bordered table-striped mt-4">
        <thead>
            <tr>
                <th>User</th>
                <th>Email</th>
                <th>Total Assigned</th>
                <th>Details</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?= htmlspecialchars($u['user_name']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><span class="badge bg-primary"><?= $u['total_assigned'] ?></span></td>

                <td>
                    <?php if (!empty($details[$u['user_id']])): ?>
                        <button class="btn btn-sm btn-info" onclick="toggleDetails('d<?= $u['user_id'] ?>')">
                            View
                        </button>

                        <div id="d<?= $u['user_id'] ?>" class="details-box" style="display:none;">
                            <table class="table table-sm table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Category</th>
                                        <th>Item</th>
                                        <th>Brand</th>
                                        <th>Model</th>
                                        <th>Serial</th>
                                        <th>Assigned On</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($details[$u['user_id']] as $d): ?>
                                    <tr>
                                        <td><?= $d['category_name'] ?></td>
                                        <td><?= $d['item_details'] ?></td>
                                        <td><?= $d['brand'] ?></td>
                                        <td><?= $d['model'] ?></td>
                                        <td><?= $d['serial_no'] ?></td>
                                        <td><?= $d['assigned_on'] ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                    <?php else: ?>
                        <span class="text-muted">No Items</span>
                    <?php endif; ?>
                </td>

            </tr>
            <?php endforeach; ?>
        </tbody>

    </table>

</div>

<script>
function toggleDetails(id) {
    let box = document.getElementById(id);
    box.style.display = (box.style.display === "none") ? "block" : "none";
}

$(document).ready(function () {
    $('#userAssignTable').DataTable({
        order: [[0, "asc"]],
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
