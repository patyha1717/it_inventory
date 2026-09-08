<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers/auth.php';

check_login();
$pdo = db();

/* -------------------------------
    FETCH FILTER OPTIONS
-------------------------------- */
$categories = $pdo->query("SELECT id, name FROM inv_categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

/* -------------------------------
    READ FILTER VALUES
-------------------------------- */
$cat_id    = isset($_GET['category_id']) ? $_GET['category_id'] : '';
$status    = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to   = isset($_GET['date_to']) ? $_GET['date_to'] : '';

/* -------------------------------
    BUILD MASTER QUERY
-------------------------------- */
$sql = "SELECT i.*, 
               c.name AS category_name, 
               u.name AS currently_assigned_to
        FROM inventory_items i
        LEFT JOIN inv_categories c ON i.category_id = c.id
        LEFT JOIN inventory_assignments ia ON i.id = ia.item_id AND ia.unassigned_on IS NULL
        LEFT JOIN users u ON ia.user_id = u.id
        WHERE 1=1";

$params = [];

if ($cat_id != '') { 
    $sql .= " AND i.category_id = ?"; 
    $params[] = $cat_id; 
}
if ($status != '') { 
    $sql .= " AND i.status = ?"; 
    $params[] = $status; 
}
if ($date_from != '') { 
    $sql .= " AND i.purchase_date >= ?"; 
    $params[] = $date_from; 
}
if ($date_to != '') { 
    $sql .= " AND i.purchase_date <= ?"; 
    $params[] = $date_to; 
}

$sql .= " ORDER BY i.purchase_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* -------------------------------
    CALCULATE DYNAMIC TOTALS
-------------------------------- */
$total_items = count($items);
$total_value = 0;

foreach ($items as $row) {
    $total_value += (float)($row['cost'] ?? 0);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Master Inventory Report | IT Inventory</title>
    <base href="/">
    <link rel="stylesheet" href="/itms.arukustech.com/public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/itms.arukustech.com/public/assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css">
    
    <style>
        /* MAXIMIZE PAGE USAGE */
        .main-content-inner { padding-top: 10px !important; }
        .table-responsive { max-height: 75vh; overflow-y: auto; border-radius: 8px; }
        
        /* HEADER COMPRESSION */
        .page-title-section { margin-bottom: 15px !important; }
        .stat-container { margin-bottom: 15px !important; }

        .sticky-top { position: sticky; top: 0; z-index: 10; background: #f8f9fa; }
        .report-card { border-radius: 12px; border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        
        th { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px; background-color: #1e293b !important; color: white !important; white-space: nowrap; padding: 12px 8px !important; }
        
        /* LARGER SERIAL NO */
        .serial-no-text { font-size: 1.1rem; font-weight: 700; color: #334155; }

        .stat-card-mini { 
            border-radius: 10px; 
            border: none; 
            color: white; 
            min-width: 200px; 
            padding: 10px 18px;
        }
        .stat-card-mini h6 { font-size: 0.6rem; text-transform: uppercase; margin-bottom: 0; opacity: 0.8; }
        .stat-card-mini h3 { font-size: 1.25rem; margin-bottom: 0; font-weight: 700; }
    </style>
</head>
<body class="bg-light">

<?php include __DIR__ . '/header.php'; ?>

<div class="main-content-inner px-4">
    
    <div class="d-flex justify-content-between align-items-center page-title-section">
        <div class="d-flex align-items-center gap-4">
            <div>
                <h4 class="fw-bold mb-0">Master Inventory Report</h4>
                <p class="text-muted small mb-0">Complete audit of assets and financial value</p>
            </div>
            <div class="d-flex gap-2 stat-container">
                <div class="stat-card-mini shadow-sm" style="background: #2563eb;">
                    <h6>TOTAL ASSETS</h6>
                    <h3><?= number_format($total_items) ?></h3>
                </div>
                <div class="stat-card-mini shadow-sm" style="background: #10b981;">
                    <h6>TOTAL VALUATION</h6>
                    <h3>&#8377;<?= number_format($total_value, 2) ?></h3>
                </div>
            </div>
        </div>
        <div class="btn-group shadow-sm">
            <button class="btn btn-success btn-sm px-3" onclick="exportExcel()"><i class="ri-file-excel-2-line"></i> Excel</button>
            <button class="btn btn-danger btn-sm px-3" onclick="exportPDF()"><i class="ri-file-pdf-line"></i> PDF</button>
        </div>
    </div>

    <div class="card report-card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label x-small fw-bold mb-1">Category</label>
                    <select name="category_id" class="form-select form-select-sm">
                        <option value="">All Categories</option>
                        <?php foreach($categories as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($cat_id == $c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label x-small fw-bold mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="available" <?= ($status == 'available') ? 'selected' : '' ?>>Available</option>
                        <option value="assigned" <?= ($status == 'assigned') ? 'selected' : '' ?>>Assigned</option>
                        <option value="maintenance" <?= ($status == 'maintenance') ? 'selected' : '' ?>>Maintenance</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label x-small fw-bold mb-1">From Date</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="<?= $date_from ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label x-small fw-bold mb-1">To Date</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="<?= $date_to ?>">
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Apply Filter</button>
                    <a href="/inventory_master.php" class="btn btn-outline-secondary btn-sm px-3">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card report-card shadow-sm">
        <div class="table-responsive">
            <table id="masterTable" class="table table-hover table-bordered align-middle mb-0" style="font-size: 0.85rem;">
                <thead class="sticky-top">
                    <tr>
                        <th class="text-center">Asset Code</th>
                        <th>Category</th>
                        <th>Item Details</th>
                        <th>Serial No</th>
                        <th class="text-end">Cost (INR)</th>
                        <th>Purchase Date</th>
                        <th>Warranty</th>
                        <th class="text-center">Status</th>
                        <th>Assigned User</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($total_items > 0): ?>
                        <?php foreach($items as $row): ?>
                        <tr>
                            <td class="text-center fw-bold text-primary"><?= htmlspecialchars($row['asset_code']) ?></td>
                            <td><span class="text-muted"><?= htmlspecialchars($row['category_name']) ?></span></td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($row['item_deatils']) ?></div>
                                <div class="text-muted x-small"><?= htmlspecialchars($row['brand']) ?> | <?= htmlspecialchars($row['model']) ?></div>
                            </td>
                            <td>
                                <span class="serial-no-text font-monospace"><?= htmlspecialchars($row['serial_no'] ?? 'N/A') ?></span>
                            </td>
                            <td class="text-end fw-bold">
                                &#8377;<?= number_format($row['cost'] ?? 0, 2) ?>
                            </td>
                            <td>
                                <div class="small fw-bold text-dark"><?= $row['purchase_date'] ?></div>
                                <div class="x-small text-muted">Inv: <?= $row['invoice_no'] ?: 'N/A' ?></div>
                            </td>
                            <td>
                                <span class="<?= (!empty($row['warranty_date']) && strtotime($row['warranty_date']) < time()) ? 'text-danger fw-bold' : 'text-muted' ?>">
                                    <?= $row['warranty_date'] ?: 'N/A' ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php 
                                    $badge = 'bg-secondary';
                                    if($row['status'] == 'available') $badge = 'bg-success';
                                    if($row['status'] == 'assigned') $badge = 'bg-primary';
                                    if($row['status'] == 'maintenance') $badge = 'bg-warning text-dark';
                                ?>
                                <span class="badge rounded-pill <?= $badge ?> px-3">
                                    <?= ucfirst($row['status']) ?>
                                </span>
                            </td>
                            <td class="fw-bold">
                                <?= $row['currently_assigned_to'] ? '<i class="ri-user-star-line me-1 text-primary"></i>'.$row['currently_assigned_to'] : '<span class="text-muted small italic">In Stock</span>' ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="9" class="text-center py-5 text-muted">No records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="/itms.arukustech.com/public/assets/js/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script>
function exportExcel() {
    var wb = XLSX.utils.table_to_book(document.getElementById("masterTable"), { sheet: "Master Inventory" });
    XLSX.writeFile(wb, "Inventory_Master_Report_<?= date('Y-m-d') ?>.xlsx");
}

function exportPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('l', 'mm', 'a3'); 
    doc.text("Master Inventory Report - &#8377;<?= number_format($total_value, 2) ?>", 15, 15);
    doc.autoTable({
        html: '#masterTable',
        startY: 25,
        theme: 'grid',
        headStyles: { fillColor: [30, 41, 59] }
    });
    doc.save("Inventory_Master_Report.pdf");
}
</script>

<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>