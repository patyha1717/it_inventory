<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers/auth.php';
auth();

$pdo = db();

/* FILTER DATA */
// NEW: Fetch Categories for filter
$categories = $pdo->query("SELECT DISTINCT name FROM rented_categories ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
$assets = $pdo->query("SELECT DISTINCT product_name FROM rented_inventory ORDER BY product_name")->fetchAll(PDO::FETCH_COLUMN);
$users = $pdo->query("SELECT id, name FROM users WHERE status=1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$projects = $pdo->query("SELECT DISTINCT project_name FROM rented_assignments WHERE project_name IS NOT NULL ORDER BY project_name ASC")->fetchAll(PDO::FETCH_COLUMN);

/* ASSIGNMENTS */
// UPDATED SQL: Added JOIN with rented_categories
$sql = "
SELECT 
 ra.id AS assign_id,
 ri.product_name,
 ri.serial_no,
 rc.name AS category_name,
 u.name AS user_name,
 u.email,
 ra.state,
 ra.city,
 ra.project_name,
 ra.assigned_at,
 ra.returned_at,
 ra.status
FROM rented_assignments ra
JOIN rented_inventory ri ON ri.id = ra.rented_inventory_id
JOIN users u ON u.id = ra.user_id
LEFT JOIN rented_categories rc ON ri.category_id = rc.id
ORDER BY ra.id DESC";
$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/header.php';
?>

<div class="main-content-inner px-4 py-3">
    <h3 class="mb-3 fw-bold">Assigned Rented Items</h3>

    <div class="card mb-3 shadow-sm border-0">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Category</label>
                    <select id="categoryFilter" class="form-control form-control-sm" onchange="applyFilters()">
                        <option value="">All Categories</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Asset</label>
                    <select id="assetFilter" class="form-control form-control-sm" onchange="applyFilters()">
                        <option value="">All Assets</option>
                        <?php foreach($assets as $a): ?>
                            <option value="<?= htmlspecialchars($a) ?>"><?= htmlspecialchars($a) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">User</label>
                    <select id="userFilter" class="form-control form-control-sm" onchange="applyFilters()">
                        <option value="">All Users</option>
                        <?php foreach($users as $u): ?>
                            <option value="<?= htmlspecialchars($u['name']) ?>"><?= htmlspecialchars($u['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Status</label>
                    <select id="statusFilter" class="form-control form-control-sm" onchange="applyFilters()">
                        <option value="">All Status</option>
                        <option value="assigned">Assigned</option>
                        <option value="returned">Returned</option>
                        <option value="unassigned">Unassigned</option> 
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label small fw-bold">From Date</label>
                    <input type="date" id="fromDate" class="form-control form-control-sm" onchange="applyFilters()">
                </div>
                <div class="col-md-1">
                    <label class="form-label small fw-bold">To Date</label>
                    <input type="date" id="toDate" class="form-control form-control-sm" onchange="applyFilters()">
                </div>
                <div class="col-md-2 d-flex gap-2 align-items-end">
                    <button type="button" class="btn btn-dark btn-sm w-100" onclick="applyFilters()">Filter</button>
                    <button type="button" class="btn btn-secondary btn-sm w-100" onclick="resetFilters()">Reset</button>
                </div>
            </div>

            <div class="mt-3 d-flex gap-2">
                <button type="button" class="btn btn-success btn-sm" onclick="exportExcel('assignTable', 'rented_report.xlsx')">Export Excel</button>
                <button type="button" class="btn btn-danger btn-sm" onclick="exportPDF('assignTable', 'rented_report.pdf')">Export PDF</button>
            </div>
        </div>
    </div>

    <form method="POST" action="api/rented_return_bulk.php">
        <button class="btn btn-warning btn-sm mb-3 fw-bold shadow-sm" onclick="return confirm('Return all selected items?')">
            <i class="fa fa-undo"></i> Return Selected Items
        </button>

        <div class="table-responsive card shadow-sm border-0">
            <table id="assignTable" class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th width="40"><input type="checkbox" id="checkAll"></th>
                        <th>ID</th>
                        <th>Category</th> <th>Item Details</th>
                        <th>Assigned To</th>
                        <th>Location</th>
                        <th>Project</th>
                        <th>Assigned On</th>
                        <th>Returned On</th>
                        <th>Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody id="reportBody">
                    <?php foreach($rows as $r): ?>
                    <tr>
                        <td>
                            <?php if($r['status']=='assigned'): ?>
                                <input type="checkbox" name="ids[]" value="<?= $r['assign_id'] ?>">
                            <?php endif; ?>
                        </td>
                        <td><?= $r['assign_id'] ?></td>
                        <td><span class="badge bg-info text-dark"><?= htmlspecialchars($r['category_name'] ?? 'N/A') ?></span></td>
                        <td>
                            <strong><?= htmlspecialchars($r['product_name']) ?></strong><br>
                            <small class="text-muted"><?= htmlspecialchars($r['serial_no']) ?></small>
                        </td>
                        <td>
                            <?= htmlspecialchars($r['user_name']) ?><br>
                            <small class="text-muted"><?= htmlspecialchars($r['email']) ?></small>
                        </td>
                        <td><small><?= htmlspecialchars($r['city']) ?>, <?= htmlspecialchars($r['state']) ?></small></td>
                        <td><?= htmlspecialchars($r['project_name']) ?></td>
                        <td><?= date('d-m-Y', strtotime($r['assigned_at'])) ?></td>
                        <td><?= $r['returned_at'] ? date('d-m-Y', strtotime($r['returned_at'])) : '-' ?></td>
                        <td>
                            <span class="badge <?= $r['status']=='assigned'?'bg-primary':'bg-success' ?>">
                                <?= ucfirst($r['status']) ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <?php if($r['status'] == 'assigned'): ?>
                                <div class="btn-group btn-group-sm">
                                    <a href="api/rented_return.php?id=<?= $r['assign_id'] ?>" 
                                       class="btn btn-outline-warning" 
                                       title="Mark as Returned" 
                                       onclick="return confirm('Mark this item as Returned?')">
                                         <i class="fa fa-rotate-left"></i>
                                    </a>
                                    
                                    <a href="api/rented_unassign.php?id=<?= $r['assign_id'] ?>" 
                                       class="btn btn-outline-danger" 
                                       title="Unassign (Delete Record)" 
                                       onclick="return confirm('WARNING: This will delete the assignment record and return the item to stock. Use only for mistakes. Proceed?')">
                                         <i class="fa fa-trash"></i>
                                    </a>
                                </div>
                            <?php else: ?>
                                <i class="fa fa-lock text-muted" title="Record Locked"></i>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </form>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script>
// ---------------- FILTER LOGIC ----------------
function applyFilters() {
    let category = document.getElementById("categoryFilter").value.toLowerCase();
    let asset = document.getElementById("assetFilter").value.toLowerCase();
    let user = document.getElementById("userFilter").value.toLowerCase();
    let status = document.getElementById("statusFilter").value.toLowerCase();
    let from = document.getElementById("fromDate").value;
    let to = document.getElementById("toDate").value;

    let rows = document.querySelectorAll("#reportBody tr");

    rows.forEach(row => {
        let rowCat = row.cells[2].innerText.toLowerCase(); // Category index 2
        let rowAsset = row.cells[3].innerText.toLowerCase(); // Shifted to 3
        let rowUser = row.cells[4].innerText.toLowerCase(); // Shifted to 4
        let rowDate = row.cells[7].innerText.trim(); // Assigned On Date index 7
        let rowStatus = row.cells[9].innerText.toLowerCase(); // Status index 9

        let show = true;
        if (category && !rowCat.includes(category)) show = false;
        if (asset && !rowAsset.includes(asset)) show = false;
        if (user && !rowUser.includes(user)) show = false;
        if (status && !rowStatus.includes(status)) show = false;
        
        // Date formatting for comparison
        if (from || to) {
            let parts = rowDate.split('-');
            let checkDate = `${parts[2]}-${parts[1]}-${parts[0]}`; // Convert d-m-Y to Y-m-d
            if (from && checkDate < from) show = false;
            if (to && checkDate > to) show = false;
        }

        row.style.display = show ? "" : "none";
    });
}

function resetFilters() {
    document.querySelectorAll('.form-control-sm').forEach(el => el.value = "");
    applyFilters();
}

// ---------------- EXPORT LOGIC ----------------
function exportExcel(tableId, filename) {
    let table = document.getElementById(tableId);
    let tableClone = table.cloneNode(true);
    let rows = tableClone.querySelectorAll('tr');
    rows.forEach(row => {
        row.removeChild(row.lastElementChild); // Remove Action
        row.removeChild(row.firstElementChild); // Remove Checkbox
    });
    let wb = XLSX.utils.table_to_book(tableClone, { sheet: "Assignments" });
    XLSX.writeFile(wb, filename);
}

async function exportPDF(tableId, filename) {
    const { jsPDF } = window.jspdf;
    let doc = new jsPDF('l', 'mm', 'a4'); 
    doc.text("Rented Assignments Report", 14, 15);
    doc.autoTable({
        html: "#" + tableId,
        startY: 20,
        theme: "grid",
        columns: [1, 2, 3, 4, 5, 6, 7, 8, 9], // Updated columns to include Category
        styles: { fontSize: 7 }
    });
    doc.save(filename);
}

document.getElementById('checkAll').addEventListener('change', function() {
    let checkboxes = document.querySelectorAll('input[name="ids[]"]');
    checkboxes.forEach(cb => {
        if (cb.closest('tr').style.display !== 'none') cb.checked = this.checked;
    });
});
</script>

<?php include __DIR__ . '/footer.php'; ?>