<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers/auth.php';
auth();

$pdo = db();

/* =======================================================
    FETCH FILTER OPTIONS
======================================================= */
$assets = $pdo->query("SELECT DISTINCT product_name FROM rented_inventory ORDER BY product_name ASC")->fetchAll(PDO::FETCH_COLUMN);
$users = $pdo->query("SELECT id, name FROM users WHERE status=1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// NEW: Fetch distinct project names from the assignments table for the filter dropdown
$projects = $pdo->query("SELECT DISTINCT project_name FROM rented_assignments WHERE project_name IS NOT NULL AND project_name != '' ORDER BY project_name ASC")->fetchAll(PDO::FETCH_COLUMN);

/* =======================================================
    FILTERS LOGIC
======================================================= */
$where = "1=1";
$params = [];

if (!empty($_GET['asset'])) {
    $where .= " AND ri.product_name = ?";
    $params[] = $_GET['asset'];
}
if (!empty($_GET['user_id'])) {
    $where .= " AND ra.user_id = ?";
    $params[] = $_GET['user_id'];
}
// NEW: Project Filter Logic
if (!empty($_GET['project_name'])) {
    $where .= " AND ra.project_name = ?";
    $params[] = $_GET['project_name'];
}
if (!empty($_GET['from'])) {
    $where .= " AND DATE(ra.assigned_at) >= ?";
    $params[] = $_GET['from'];
}
if (!empty($_GET['to'])) {
    $where .= " AND DATE(ra.assigned_at) <= ?";
    $params[] = $_GET['to'];
}

/* =======================================================
    FETCH DATA
======================================================= */
$sql = "
SELECT 
    ra.id,
    ri.product_name,
    ri.serial_no,
    u_assigned.name AS assigned_to_user,
    u_admin.name AS action_by_user,
    ra.project_name,
    ra.city,
    ra.state,
    ra.assigned_at,
    ra.returned_at,
    ra.status
FROM rented_assignments ra
JOIN rented_inventory ri ON ri.id = ra.rented_inventory_id
JOIN users u_assigned ON u_assigned.id = ra.user_id
LEFT JOIN users u_admin ON u_admin.id = ra.assigned_by
WHERE $where
ORDER BY ra.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/header.php';
?>

<style>
    .table thead th {
        background: #1e293b !important;
        color: white !important;
        font-weight: 500 !important;
        text-transform: uppercase;
        font-size: 0.75rem;
    }
    .badge-assigned { background-color: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; }
    .badge-returned { background-color: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; }
</style>

<div class="main-content-inner px-4 py-3 d-block w-100">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold m-0">Rented Asset Lifecycle Report</h3>
            <div>
                <button onclick="exportExcel('rentedLifecycleTable', 'Rented_Report.xlsx')" class="btn btn-success btn-sm me-2">
                    <i class="fa fa-file-excel"></i> Excel
                </button>
                <button onclick="exportPDF('rentedLifecycleTable', 'Rented_Report.pdf')" class="btn btn-danger btn-sm">
                    <i class="fa fa-file-pdf"></i> PDF
                </button>
            </div>
        </div>

        <form method="GET" class="card p-4 mb-4 bg-white shadow-sm border-0">
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="small fw-bold mb-1">Asset Name</label>
                    <select name="asset" class="form-select form-select-sm">
                        <option value="">All Assets</option>
                        <?php foreach ($assets as $a): ?>
                            <option value="<?= htmlspecialchars($a) ?>" <?= (($_GET['asset'] ?? '') == $a) ? 'selected' : '' ?>><?= htmlspecialchars($a) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold mb-1">Assigned To</label>
                    <select name="user_id" class="form-select form-select-sm">
                        <option value="">All Employees</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= (($_GET['user_id'] ?? '') == $u['id']) ? 'selected' : '' ?>><?= htmlspecialchars($u['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="small fw-bold mb-1">Project Name</label>
                    <select name="project_name" class="form-select form-select-sm">
                        <option value="">All Projects</option>
                        <?php foreach ($projects as $pj): ?>
                            <option value="<?= htmlspecialchars($pj) ?>" <?= (($_GET['project_name'] ?? '') == $pj) ? 'selected' : '' ?>><?= htmlspecialchars($pj) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="small fw-bold mb-1">From</label>
                    <input type="date" name="from" class="form-control form-control-sm" value="<?= $_GET['from'] ?? '' ?>">
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold mb-1">To</label>
                    <input type="date" name="to" class="form-control form-control-sm" value="<?= $_GET['to'] ?? '' ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="btn-group w-100">
                        <button class="btn btn-primary btn-sm py-2">Filter</button>
                        <a href="rented_reports.php" class="btn btn-secondary btn-sm py-2">Reset</a>
                    </div>
                </div>
            </div>
        </form>

        <div class="card p-0 bg-white border-0 shadow-sm">
            <div class="table-responsive">
                <table id="rentedLifecycleTable" class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">ID</th>
                            <th>Item & Serial</th>
                            <th>Assigned To</th>
                            <th>Action By</th>
                            <th>Project/Location</th>
                            <th>In Date</th>
                            <th>Out Date</th>
                            <th class="pe-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $l): ?>
                        <tr>
                            <td class="ps-3 text-muted">#<?= $l['id'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($l['product_name']) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($l['serial_no']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($l['assigned_to_user']) ?></td>
                            <td><span class="small text-secondary"><?= htmlspecialchars($l['action_by_user'] ?? 'System') ?></span></td>
                            <td>
                                <div class="fw-bold small"><?= htmlspecialchars($l['project_name'] ?: 'N/A') ?></div>
                                <div class="x-small text-muted"><?= htmlspecialchars($l['city']) ?>, <?= htmlspecialchars($l['state']) ?></div>
                            </td>
                            <td class="small"><?= date('d-m-Y', strtotime($l['assigned_at'])) ?></td>
                            <td class="small"><?= $l['returned_at'] ? date('d-m-Y', strtotime($l['returned_at'])) : '<span class="text-success font-italic">Active</span>' ?></td>
                            <td class="pe-3 text-center">
                                <span class="badge <?= $l['status'] == 'assigned' ? 'badge-assigned' : 'badge-returned' ?>">
                                    <?= ucfirst($l['status']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($reports)): ?>
                            <tr><td colspan="8" class="text-center py-4">No data found for the selected filters.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script>
function exportExcel(tableId, filename) {
    let table = document.getElementById(tableId);
    let wb = XLSX.utils.table_to_book(table, { sheet: "Lifecycle" });
    XLSX.writeFile(wb, filename);
}

async function exportPDF(tableId, filename) {
    const { jsPDF } = window.jspdf;
    let doc = new jsPDF('l', 'mm', 'a4'); 
    doc.text("Rented Asset Lifecycle Report", 14, 15);
    doc.autoTable({ html: "#" + tableId, startY: 20, theme: 'grid', headStyles: {fillColor: [30, 41, 59]} });
    doc.save(filename);
}
</script>

<?php include __DIR__ . '/footer.php'; ?>