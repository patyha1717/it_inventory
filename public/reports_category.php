<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers/auth.php';
auth();

$pdo = db();

/* Fetch summary category-wise */
$sql = "
    SELECT 
        c.name AS category,
        COUNT(i.id) AS total,
        SUM(i.status = 'available') AS available,
        SUM(i.status = 'assigned') AS assigned,
        SUM(i.status = 'maintenance') AS maintenance,
        SUM(i.status = 'retired') AS retired
    FROM inv_categories c
    LEFT JOIN inventory_items i ON i.category_id = c.id
    GROUP BY c.id
    ORDER BY c.name ASC
";

$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/header.php';
?>

<style>
.table thead th {
    background:#1e293b !important;
    color:white !important;
}
</style>

<!-- <div class="container mt-4"> -->
    <div class="main-content-inner px-4 py-3">

    <h3>Category Wise Asset Report</h3>

    <table id="categoryTable" class="table table-bordered table-striped mt-4">
        <thead>
            <tr>
                <th>Category</th>
                <th>Total</th>
                <th>Available</th>
                <th>Assigned</th>
                <th>Maintenance</th>
                <th>Retired</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['category']) ?></td>
                <td><?= $r['total'] ?></td>
                <td><?= $r['available'] ?></td>
                <td><?= $r['assigned'] ?></td>
                <td><?= $r['maintenance'] ?></td>
                <td><?= $r['retired'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>

    </table>

</div>

<script>
$(document).ready(function () {

    $('#categoryTable').DataTable({
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
