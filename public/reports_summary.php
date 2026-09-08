<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers/auth.php';
auth();

$pdo = db();

/* Total counts */
$total      = $pdo->query("SELECT COUNT(*) FROM inventory_items")->fetchColumn();
$available  = $pdo->query("SELECT COUNT(*) FROM inventory_items WHERE status='available'")->fetchColumn();
$assigned   = $pdo->query("SELECT COUNT(*) FROM inventory_items WHERE status='assigned'")->fetchColumn();
$maint      = $pdo->query("SELECT COUNT(*) FROM inventory_items WHERE status='maintenance'")->fetchColumn();
$faulty     = $pdo->query("SELECT COUNT(*) FROM inventory_items WHERE status='Faulty'")->fetchColumn();
$retired    = $pdo->query("SELECT COUNT(*) FROM inventory_items WHERE status='retired'")->fetchColumn();

/* Category wise breakdown */
$categories = $pdo->query("
    SELECT c.name AS category,
           COUNT(i.id) AS total,
           SUM(CASE WHEN i.status='available' THEN 1 ELSE 0 END) AS available,
           SUM(CASE WHEN i.status='assigned' THEN 1 ELSE 0 END) AS assigned,
           SUM(CASE WHEN i.status='maintenance' THEN 1 ELSE 0 END) AS maintenance,
           SUM(CASE WHEN i.status='Faulty' THEN 1 ELSE 0 END) AS Faulty,
           SUM(CASE WHEN i.status='retired' THEN 1 ELSE 0 END) AS retired
    FROM inv_categories c
    LEFT JOIN inventory_items i ON c.id = i.category_id
    GROUP BY c.id ORDER BY c.name ASC
")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/header.php';
?>

<style>
    /* MAXIMIZE PAGE USAGE & SHIFT UP */
    .main-content-inner { padding-top: 5px !important; margin-top: -10px; }
    
    /* SUMMARY CARD STYLING */
    .stat-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    .stat-card h5 { font-size: 0.65rem; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; margin-bottom: 5px; opacity: 0.9; }
    .stat-card h3 { font-size: 1.6rem; font-weight: 800; margin-bottom: 0; }

    /* CUSTOM FAULTY COLOR (Indigo/Slate mix) */
    .bg-faulty { background-color: #6366f1 !important; color: white !important; }
    .text-faulty { color: #6366f1 !important; font-weight: bold; }

    /* TABLE STYLING */
    #reportTable thead th {
        background: #1e293b !important;
        color: white !important;
        font-size: 0.75rem;
        text-transform: uppercase;
        border: none;
        padding: 12px 10px !important;
    }
    .table-responsive { border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
</style>

<div class="main-content-inner px-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-0">Inventory Summary Report</h4>
            <p class="text-muted small mb-0">Status-wise distribution of all company assets</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="card stat-card p-3 text-center bg-primary text-white">
                <h5>Total Assets</h5>
                <h3><?= number_format($total) ?></h3>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card stat-card p-3 text-center bg-success text-white">
                <h5>Available</h5>
                <h3><?= number_format($available) ?></h3>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card stat-card p-3 text-center bg-info text-white">
                <h5>Assigned</h5>
                <h3><?= number_format($assigned) ?></h3>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card stat-card p-3 text-center bg-warning text-dark">
                <h5>Maintenance</h5>
                <h3><?= number_format($maint) ?></h3>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card stat-card p-3 text-center bg-faulty">
                <h5>Faulty</h5>
                <h3><?= number_format($faulty) ?></h3>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card stat-card p-3 text-center bg-danger text-white">
                <h5>Retired</h5>
                <h3><?= number_format($retired) ?></h3>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="reportTable" class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Category Name</th>
                            <th class="text-center">Total</th>
                            <th class="text-center">Available</th>
                            <th class="text-center">Assigned</th>
                            <th class="text-center">Maintenance</th>
                            <th class="text-center">Faulty</th>
                            <th class="text-center">Retired</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $c): ?>
                        <tr>
                            <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($c['category']) ?></td>
                            <td class="text-center fw-bold text-primary"><?= $c['total'] ?></td>
                            <td class="text-center text-success fw-bold"><?= $c['available'] ?: '0' ?></td>
                            <td class="text-center text-info fw-bold"><?= $c['assigned'] ?: '0' ?></td>
                            <td class="text-center text-warning fw-bold"><?= $c['maintenance'] ?: '0' ?></td>
                            <td class="text-center text-faulty"><?= $c['Faulty'] ?: '0' ?></td>
                            <td class="text-center text-danger fw-bold"><?= $c['retired'] ?: '0' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    $('#reportTable').DataTable({
        dom: 'Bfrtip',
        pageLength: 25,
        ordering: true,
        buttons: [
            { 
                extend: 'excel', 
                className: 'btn btn-success btn-sm px-3 mx-1', 
                text: '<i class="ri-file-excel-2-line"></i> Excel' 
            },
            { 
                extend: 'pdf', 
                className: 'btn btn-danger btn-sm px-3 mx-1', 
                text: '<i class="ri-file-pdf-line"></i> PDF' 
            }
        ]
    });
});
</script>

<?php include __DIR__ . '/footer.php'; ?>