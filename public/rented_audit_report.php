<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers/auth.php';
auth();

$pdo = db();

/* --- FETCH FILTER OPTIONS --- */
$assets = $pdo->query("SELECT DISTINCT product_name FROM rented_inventory ORDER BY product_name ASC")->fetchAll(PDO::FETCH_COLUMN);
$admins = $pdo->query("SELECT DISTINCT u.id, u.name FROM users u JOIN rented_audit_log l ON l.user_id = u.id")->fetchAll(PDO::FETCH_ASSOC);

/* --- FILTER VALUES --- */
$f_asset  = $_GET['asset'] ?? "";
$f_user   = $_GET['user_id'] ?? "";
$f_action = $_GET['action'] ?? "";
$f_from   = $_GET['from'] ?? "";
$f_to     = $_GET['to'] ?? "";

/* --- DYNAMIC QUERY --- */
$sql = "
SELECT 
    l.*, 
    u.name AS admin_name,
    ri.product_name,
    ri.serial_no
FROM rented_audit_log l
LEFT JOIN users u ON u.id = l.user_id
LEFT JOIN rented_inventory ri ON ri.id = l.asset_id
WHERE 1=1
";

$params = [];
if ($f_asset != "") { $sql .= " AND ri.product_name = ?"; $params[] = $f_asset; }
if ($f_user  != "") { $sql .= " AND l.user_id = ?"; $params[] = $f_user; }
if ($f_action != "") { $sql .= " AND l.action = ?"; $params[] = $f_action; }
if ($f_from != "") { $sql .= " AND DATE(l.created_at) >= ?"; $params[] = $f_from; }
if ($f_to != "") { $sql .= " AND DATE(l.created_at) <= ?"; $params[] = $f_to; }

$sql .= " ORDER BY l.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/header.php';
?>

<div class="main-content-inner px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="fw-bold"><i class="fa fa-history text-primary"></i> Rented Audit History</h3>
        <div class="d-flex gap-2">
            <button class="btn btn-danger btn-sm shadow-sm" onclick="exportPDF()">
                <i class="fa fa-file-pdf"></i> Export PDF
            </button>
            <button class="btn btn-success btn-sm shadow-sm" onclick="exportExcel()">
                <i class="fa fa-file-excel"></i> Export Excel
            </button>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-3">
                    <label class="small fw-bold">Asset</label>
                    <select name="asset" class="form-select form-select-sm">
                        <option value="">All Assets</option>
                        <?php foreach($assets as $name): ?>
                            <option value="<?= $name ?>" <?= $f_asset==$name?'selected':'' ?>><?= $name ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold">Admin</label>
                    <select name="user_id" class="form-select form-select-sm">
                        <option value="">All Admins</option>
                        <?php foreach($admins as $a): ?>
                            <option value="<?= $a['id'] ?>" <?= $f_user==$a['id']?'selected':'' ?>><?= $a['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold">Action</label>
                    <select name="action" class="form-select form-select-sm">
                        <option value="">All Actions</option>
                        <option value="assign" <?= $f_action=='assign'?'selected':'' ?>>Assign</option>
                        <option value="unassign" <?= $f_action=='unassign'?'selected':'' ?>>Unassign</option>
                        <option value="return" <?= $f_action=='return'?'selected':'' ?>>Return</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold">From</label>
                    <input type="date" name="from" value="<?= $f_from ?>" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold">To</label>
                    <input type="date" name="to" value="<?= $f_to ?>" class="form-control form-control-sm">
                </div>
                <div class="col-md-1 d-flex align-items-end gap-1">
                    <button type="submit" class="btn btn-dark btn-sm w-100">Filter</button>
                    <a href="rented_audit_report.php" class="btn btn-outline-secondary btn-sm"><i class="fa fa-refresh"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="auditTable">
                <thead class="table-dark">
                    <tr>
                        <th width="180">Date & Time</th>
                        <th>Admin User</th>
                        <th>Asset & Serial</th>
                        <th>Action</th>
                        <th>Activity Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $row): ?>
                    <tr>
                        <td class="small"><?= date('d M Y, h:i A', strtotime($row['created_at'])) ?></td>
                        <td><strong><?= htmlspecialchars($row['admin_name'] ?? 'System') ?></strong></td>
                        <td>
                            <?= htmlspecialchars($row['product_name']) ?><br>
                            <small class="text-muted">SN: <?= htmlspecialchars($row['serial_no']) ?></small>
                        </td>
                        <td>
                            <?php 
                                $class = 'bg-secondary';
                                if($row['action'] == 'assign') $class = 'bg-primary';
                                if($row['action'] == 'unassign') $class = 'bg-danger';
                                if($row['action'] == 'return') $class = 'bg-warning text-dark';
                            ?>
                            <span class="badge <?= $class ?>"><?= strtoupper($row['action']) ?></span>
                        </td>
                        <td class="small text-muted"><?= htmlspecialchars($row['description']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($logs)): ?>
                        <tr><td colspan="5" class="text-center py-4">No audit logs found for the selected criteria.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script>
function exportExcel() {
    let table = document.getElementById("auditTable");
    let wb = XLSX.utils.table_to_book(table, { sheet: "Rented Audit" });
    XLSX.writeFile(wb, "Rented_Audit_Report.xlsx");
}

async function exportPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('l', 'mm', 'a4');
    doc.setFontSize(18);
    doc.text("Rented Asset Audit Report", 14, 15);
    doc.autoTable({
        html: '#auditTable',
        startY: 25,
        theme: 'grid',
        headStyles: { fillColor: [40, 44, 52] },
        styles: { fontSize: 8 }
    });
    doc.save("Rented_Audit_Report.pdf");
}
</script>

<?php include __DIR__ . '/footer.php'; ?>