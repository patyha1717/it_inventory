<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers/auth.php';
auth();

$pdo = db();

/* Fetch all assets with category */
$items = $pdo->query("
    SELECT i.*, c.name AS category_name
    FROM inventory_items i
    LEFT JOIN inv_categories c ON c.id = i.category_id
    ORDER BY i.warranty_date ASC
")->fetchAll(PDO::FETCH_ASSOC);

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

    <h3>Purchase / Warranty Report</h3>

    <table id="warrantyTable" class="table table-bordered table-striped mt-4">
        <thead>
            <tr>
                <th>Category</th>
                <th>Item</th>
                <th>Brand</th>
                <th>Model</th>
                <th>Serial</th>
                <th>Purchase Date</th>
                <th>Warranty Till</th>
                <th>Status</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($items as $i): ?>

            <?php
                // Warranty Status Logic
                $today = date("Y-m-d");
                $expiry = $i['warranty_date'];

                if ($expiry == "0000-00-00" || $expiry == null) {
                    $status = "<span class='badge bg-secondary'>No Warranty</span>";
                }
                elseif ($expiry < $today) {
                    $status = "<span class='badge bg-danger'>Expired</span>";
                }
                elseif (strtotime($expiry) <= strtotime("+60 days")) {
                    $status = "<span class='badge bg-warning text-dark'>Expiring Soon</span>";
                }
                else {
                    $status = "<span class='badge bg-success'>Active</span>";
                }
            ?>

            <tr>
                <td><?= htmlspecialchars($i['category_name']) ?></td>
                <td><?= htmlspecialchars($i['item_details']) ?></td>
                <td><?= htmlspecialchars($i['brand']) ?></td>
                <td><?= htmlspecialchars($i['model']) ?></td>
                <td><?= htmlspecialchars($i['serial_no']) ?></td>
                <td><?= $i['purchase_date'] ?></td>
                <td><?= $i['warranty_date'] ?></td>
                <td><?= $status ?></td>
            </tr>

            <?php endforeach; ?>
        </tbody>

    </table>

</div>

<script>
$(document).ready(function () {

    $('#warrantyTable').DataTable({
        order: [[6, "asc"]], // sort by warranty date
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
