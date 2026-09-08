<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers/auth.php';
auth();

$pdo = db();

// Get filters from URL
$status_filter = $_GET['status'] ?? 'all';
$category_id   = $_GET['category_id'] ?? null;

// Dynamic Query building
$where = "1=1";
$params = [];
$report_title = "Inventory Report";

// Handle Status Filter
if ($status_filter !== 'all') {
    $where .= " AND i.status = ?";
    $params[] = $status_filter;
    $report_title = ucfirst($status_filter) . " Inventory Report";
}

// Handle Category Filter
if ($category_id) {
    $where .= " AND i.category_id = ?";
    $params[] = $category_id;
    
    // Fetch category name for the title
    $catStmt = $pdo->prepare("SELECT name FROM inv_categories WHERE id = ?");
    $catStmt->execute([$category_id]);
    $catName = $catStmt->fetchColumn();
    if ($catName) { $report_title = $catName . " Category Report"; }
}

$sql = "SELECT i.*, c.name AS category_name, u.name AS assigned_user
        FROM inventory_items i
        LEFT JOIN inv_categories c ON i.category_id = c.id
        LEFT JOIN inventory_assignments ia ON i.id = ia.item_id AND ia.unassigned_on IS NULL
        LEFT JOIN users u ON ia.user_id = u.id
        WHERE $where
        ORDER BY i.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/header.php';
?>

<div class="main-content-inner px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-capitalize"><?= htmlspecialchars($report_title) ?></h3>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
            <i class="ri-arrow-left-line"></i> Back to Dashboard
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <table id="masterReportTable" class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>SR#</th>
                        <th>Category</th>
                        <th>Item Details</th>
                        <th>Serial / Asset Code</th>
                        <th>Status</th>
                        <th>Assigned To</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $sr = 1; foreach ($items as $row): ?>
                    <tr>
                        <td><?= $sr++ ?></td>
                        <td><?= htmlspecialchars($row['category_name']) ?></td>
                        <td><?= htmlspecialchars($row['item_details']) ?></td>
                        <td>
                            <small class="d-block text-muted"><?= htmlspecialchars($row['serial_no']) ?></small>
                            <strong><?= htmlspecialchars($row['asset_code']) ?></strong>
                        </td>
                        <td>
                            <?php 
                            $badge = [
                                'available'   => 'bg-success',
                                'assigned'    => 'bg-primary',
                                'faulty'      => 'bg-danger',
                                'maintenance' => 'bg-warning text-dark',
                                'missing'     => 'bg-info',
                                'retired'     => 'bg-secondary'
                            ][$row['status']] ?? 'bg-secondary';
                            ?>
                            <span class="badge <?= $badge ?> text-capitalize"><?= $row['status'] ?></span>
                        </td>
                        <td><?= htmlspecialchars($row['assigned_user'] ?? 'N/A') ?></td>
                        <td><?= date('d-M-Y', strtotime($row['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#masterReportTable').DataTable({
        dom: 'Bfrtip', 
        buttons: [
            {
                extend: 'excelHtml5',
                text: '<i class="ri-file-excel-2-line"></i> Export Excel',
                className: 'btn btn-success btn-sm me-2',
                title: '<?= str_replace(" ", "_", $report_title) ?>'
            },
            {
                extend: 'pdfHtml5',
                text: '<i class="ri-file-pdf-line"></i> Export PDF',
                className: 'btn btn-danger btn-sm',
                title: '<?= str_replace(" ", "_", $report_title) ?>',
                orientation: 'landscape',
                pageSize: 'A4'
            }
        ],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search report..."
        }
    });
});
</script>

<?php include __DIR__ . '/footer.php'; ?>