<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers/auth.php';
auth();

$pdo = db();

// --- START EXPORT SECTION ---
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $where_export = "1=1";
    $params_export = [];
    if(!empty($_GET['project'])) { $where_export .= " AND (ri.project_name = ? OR ra.project_name = ?)"; $params_export[] = $_GET['project']; $params_export[] = $_GET['project']; }
    if(!empty($_GET['user_id'])) { $where_export .= " AND ra.user_id = ?"; $params_export[] = $_GET['user_id']; }
    if(!empty($_GET['from'])) { $where_export .= " AND ra.assigned_at >= ?"; $params_export[] = $_GET['from']." 00:00:00"; }
    if(!empty($_GET['to'])) { $where_export .= " AND ra.assigned_at <= ?"; $params_export[] = $_GET['to']." 23:59:59"; }

    $sql_export = "SELECT 
                    ri.product_name, ri.serial_no, 
                    COALESCE(ra.project_name, ri.project_name) as project,
                    ri.company_name, ri.vendor_name,
                    u1.name as handed_to, u2.name as handed_by,
                    ra.assigned_at, ra.returned_at, ra.city, ra.state,
                    CASE 
                        WHEN u1.name IS NOT NULL AND ra.returned_at IS NULL THEN 'Assigned'
                        WHEN ra.returned_at IS NOT NULL THEN 'Returned'
                        ELSE 'Available'
                    END as current_status
                FROM rented_inventory ri
                LEFT JOIN rented_assignments ra ON ri.id = ra.rented_inventory_id
                LEFT JOIN users u1 ON ra.user_id = u1.id
                LEFT JOIN users u2 ON ra.assigned_by = u2.id
                WHERE $where_export ORDER BY ra.id DESC";

    $stmt_export = $pdo->prepare($sql_export);
    $stmt_export->execute($params_export);
    $export_rows = $stmt_export->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="Rented_Asset_Report_'.date('Y-m-d').'.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Product Name', 'Serial No', 'Project', 'Company', 'Vendor', 'Status', 'Handed To', 'Location', 'Handed By', 'Assign Date', 'Return Date']);
    
    foreach ($export_rows as $row) {
        fputcsv($output, [
            $row['product_name'], 
            $row['serial_no'], 
            $row['project'], 
            $row['company_name'] ?? 'Company IT Solutions', 
            $row['vendor_name'] ?? 'Company IT Solutions', 
            $row['current_status'],
            $row['handed_to'] ?? '—', 
            $row['city'] ? $row['city'].', '.$row['state'] : '—',
            $row['handed_by'] ?? 'Admin', 
            $row['assigned_at'] ? date('d-m-Y', strtotime($row['assigned_at'])) : '—', 
            $row['returned_at'] ? date('d-m-Y', strtotime($row['returned_at'])) : ($row['current_status'] == 'Assigned' ? 'In Possession' : '—')
        ]);
    }
    fclose($output);
    exit;
}
// --- END EXPORT SECTION ---

// 1. SUMMARY STATS
$stats = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(status = 'available') as available,
        SUM(status = 'assigned') as assigned,
        (SELECT COUNT(*) FROM rented_assignments WHERE returned_at IS NOT NULL) as total_returns
    FROM rented_inventory
")->fetch(PDO::FETCH_ASSOC);

// 2. DROPDOWN DATA
$projects = $pdo->query("SELECT DISTINCT project_name FROM rented_inventory WHERE project_name IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
$users = $pdo->query("SELECT id, name FROM users ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/header.php';
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>

<style>
    .stat-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        cursor: pointer;
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.15) !important;
    }
    .table-dark { background-color: #1e293b !important; }
</style>

<div class="main-content-inner px-4 py-3">
    <div class="mb-4">
        <h3 class="fw-bold mb-0">Rented Asset Master Report</h3>
        </div>

    <div class="row mb-3">
        <div class="col-md-3">
            <div class="card stat-card bg-primary text-white border-0 shadow-sm p-3">
                <small class="opacity-75">Total Inventory</small>
                <h3 class="mb-0 fw-bold"><?= $stats['total'] ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-success text-white border-0 shadow-sm p-3">
                <small class="opacity-75">Available Items</small>
                <h3 class="mb-0 fw-bold"><?= $stats['available'] ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-warning text-dark border-0 shadow-sm p-3">
                <small class="opacity-75">Currently Assigned</small>
                <h3 class="mb-0 fw-bold"><?= $stats['assigned'] ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-info text-white border-0 shadow-sm p-3">
                <small class="opacity-75">Returned History</small>
                <h3 class="mb-0 fw-bold"><?= $stats['total_returns'] ?></h3>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4 bg-light">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Project</label>
                    <select name="project" class="form-select select2">
                        <option value="">All Projects</option>
                        <?php foreach($projects as $p): ?>
                            <option value="<?= $p ?>" <?= ($_GET['project']??'') == $p ? 'selected' : '' ?>><?= $p ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Assigned To</label>
                    <select name="user_id" class="form-select select2">
                        <option value="">All Employees</option>
                        <?php foreach($users as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= ($_GET['user_id']??'') == $u['id'] ? 'selected' : '' ?>><?= $u['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">From Date</label>
                    <input type="date" name="from" class="form-control" value="<?= $_GET['from']??'' ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">To Date</label>
                    <input type="date" name="to" class="form-control" value="<?= $_GET['to']??'' ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-1">
                    <button type="submit" class="btn btn-dark flex-grow-1">Filter</button>
                    <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="btn btn-success" title="Export CSV">
                         <i class="fas fa-file-csv"></i>
                    </a>
                    <button type="button" onclick="exportMasterPDF()" class="btn btn-danger" title="Export PDF">
                    <i class="fas fa-file-pdf"></i>
                     </button>                
			</div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <table id="masterReportTable" class="table table-hover w-100">
                <thead class="table-dark">
                    <tr>
                        <th>SR#</th>
                        <th>Product Name</th>
                        <th>Serial No</th>
                        <th>Project</th>
                        <th>Company Name</th> 
                        <th>Vendor Name</th>  
                        <th>Status</th>
                        <th>Handed To</th>
                        <th>Location</th>
                        <th>Handed By</th>
                        <th>Assign Date</th>
                        <th>Return Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $where = "1=1";
                    $params = [];
                    if(!empty($_GET['project'])) { $where .= " AND (ri.project_name = ? OR ra.project_name = ?)"; $params[] = $_GET['project']; $params[] = $_GET['project']; }
                    if(!empty($_GET['user_id'])) { $where .= " AND ra.user_id = ?"; $params[] = $_GET['user_id']; }
                    if(!empty($_GET['from'])) { $where .= " AND ra.assigned_at >= ?"; $params[] = $_GET['from']." 00:00:00"; }
                    if(!empty($_GET['to'])) { $where .= " AND ra.assigned_at <= ?"; $params[] = $_GET['to']." 23:59:59"; }

                    $sql = "SELECT 
                                ri.product_name, ri.serial_no, ri.project_name as orig_proj,
                                ri.company_name, ri.vendor_name,
                                u1.name as handed_to, u2.name as handed_by,
                                ra.assigned_at, ra.returned_at, ra.city, ra.state, ra.project_name as current_proj
                            FROM rented_inventory ri
                            LEFT JOIN rented_assignments ra ON ri.id = ra.rented_inventory_id
                            LEFT JOIN users u1 ON ra.user_id = u1.id
                            LEFT JOIN users u2 ON ra.assigned_by = u2.id
                            WHERE $where ORDER BY ra.id DESC";

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    $sr = 1;
                    foreach ($rows as $r):
                        $status = "Available"; $badge = "bg-success";
                        if($r['handed_to'] && empty($r['returned_at'])) { $status = "Assigned"; $badge = "bg-warning text-dark"; }
                        elseif(!empty($r['returned_at'])) { $status = "Returned"; $badge = "bg-secondary"; }
                    ?>
                    <tr>
                        <td><?= $sr++ ?></td>
                        <td><strong><?= htmlspecialchars($r['product_name']) ?></strong></td>
                        <td><code><?= htmlspecialchars($r['serial_no']) ?></code></td>
                        <td><?= htmlspecialchars($r['current_proj'] ?? $r['orig_proj']) ?></td>
                        <td><?= htmlspecialchars($r['company_name'] ?? 'Company IT Solutions') ?></td>
                        <td><?= htmlspecialchars($r['vendor_name'] ?? 'Company IT Solutions') ?></td>
                        <td><span class="badge <?= $badge ?>"><?= $status ?></span></td>
                        <td><?= $r['handed_to'] ?? '—' ?></td>
                        <td><?= $r['city'] ? htmlspecialchars($r['city'].", ".$r['state']) : '—' ?></td>
                        <td><?= $r['handed_by'] ?? 'Admin' ?></td>
                        <td><?= $r['assigned_at'] ? date('d-m-Y', strtotime($r['assigned_at'])) : '—' ?></td>
                        <td><?= $r['returned_at'] ? date('d-m-Y', strtotime($r['returned_at'])) : ($status == "Assigned" ? 'In Possession' : '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script>
$(document).ready(function() {
    // Standard DataTable Initialization
    $('#masterReportTable').DataTable({
        pageLength: 25,
        order: [[0, 'asc']]
    });
});

// RESOLVED PDF FUNCTION
function exportMasterPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('l', 'mm', 'a4'); // Landscape orientation

    // Title
    doc.setFontSize(16);
    doc.text("Company IT Solutions - Rented Asset Report", 14, 15);
    doc.setFontSize(10);
    doc.text("Generated on: " + new Date().toLocaleDateString(), 14, 22);

    // AutoTable Logic
    doc.autoTable({
        html: '#masterReportTable',
        startY: 25,
        theme: 'grid',
        styles: { fontSize: 7, cellPadding: 2 },
        headStyles: { fillColor: [30, 41, 59], textColor: [255, 255, 255] },
        margin: { left: 10, right: 10 }
    });

    doc.save('Rented_Asset_Master_Report.pdf');
}
</script>

<?php include __DIR__ . '/footer.php'; ?>