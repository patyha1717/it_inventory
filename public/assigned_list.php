<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers/auth.php';

auth();

$pdo = db();

/* -----------------------------------
   FETCH DATA FOR FILTERS (FROM CODE 2 LOGIC)
------------------------------------*/
$categories = $pdo->query("SELECT DISTINCT name FROM inv_categories ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
$assets = $pdo->query("SELECT DISTINCT item_details FROM inventory_items ORDER BY item_details")->fetchAll(PDO::FETCH_COLUMN);
$users = $pdo->query("SELECT id, name FROM users WHERE status=1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

/* -----------------------------------
   FETCH ASSIGNED ITEMS WITH CATEGORY & LOCATION
------------------------------------*/
$sql = "
SELECT 
    a.*, 
    u.name AS username, 
    u.email,
    i.item_details, 
    i.serial_no,
    c.name AS category_name,
    a.state,
    a.city
FROM inventory_assignments a
LEFT JOIN users u ON a.user_id = u.id
LEFT JOIN inventory_items i ON a.item_id = i.id
LEFT JOIN inv_categories c ON i.category_id = c.id
WHERE a.unassigned_on IS NULL
ORDER BY a.assigned_on DESC
";

$assigned = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/header.php';
?>

<div class="main-content-inner px-4 py-3">
    <h3 class="fw-bold mb-3">Assigned Items Management</h3>

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
                    <label class="form-label small fw-bold">Asset Name</label>
                    <select id="assetFilter" class="form-control form-control-sm" onchange="applyFilters()">
                        <option value="">All Assets</option>
                        <?php foreach($assets as $ast): ?>
                            <option value="<?= htmlspecialchars($ast) ?>"><?= htmlspecialchars($ast) ?></option>
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
                    <label class="form-label small fw-bold">From Date</label>
                    <input type="date" id="fromDate" class="form-control form-control-sm" onchange="applyFilters()">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">To Date</label>
                    <input type="date" id="toDate" class="form-control form-control-sm" onchange="applyFilters()">
                </div>
                <div class="col-md-2 d-flex gap-2 align-items-end">
                    <button type="button" class="btn btn-dark btn-sm w-100" onclick="applyFilters()">Filter</button>
                    <button type="button" class="btn btn-secondary btn-sm w-100" onclick="resetFilters()">Reset</button>
                </div>
            </div>

            <div class="mt-3 d-flex gap-2">
                <button type="button" class="btn btn-success btn-sm" onclick="exportExcel('assignTable', 'inventory_report.xlsx')">
                    <i class="fa fa-file-excel"></i> Export Excel
                </button>
                <button type="button" class="btn btn-danger btn-sm" onclick="exportPDF('assignTable', 'inventory_report.pdf')">
                    <i class="fa fa-file-pdf"></i> Export PDF
                </button>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table id="assignTable" class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th width="40">SR#</th>
                        <th>Category</th>
                        <th>Item Details</th>
                        <th>User</th>
                        <th>Location</th>
                        <th>Assigned On</th>
                        <th class="text-center" width="140">Actions</th>
                    </tr>
                </thead>
                <tbody id="reportBody">
                    <?php if (empty($assigned)): ?>
                        <tr><td colspan="7" class="text-center py-3">No assigned items found.</td></tr>
                    <?php endif; ?>

                    <?php 
    // 1. Initialize the sequence counter
    $sr_no = 1; 
    foreach ($assigned as $a): 
    ?>
        <tr>
            <td><?= $sr_no++ ?></td>
                            <td><span class="badge bg-info text-dark"><?= htmlspecialchars($a['category_name'] ?? 'N/A') ?></span></td>
                            <td>
                                <strong><?= htmlspecialchars($a['item_details']) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($a['serial_no'] ?? 'No Serial') ?></small>
                            </td>
                            <td>
                                <?= htmlspecialchars($a['username']) ?>
                                <div class="text-muted small"><?= htmlspecialchars($a['email']) ?></div>
                            </td>
                            <td>
                                <small><?= htmlspecialchars($a['city'] ?? 'N/A') ?>, <?= htmlspecialchars($a['state'] ?? 'N/A') ?></small>
                            </td>
                            <td><?= date('d-m-Y', strtotime($a['assigned_on'])) ?></td>
                            <td class="text-center">
                                <?php if (is_admin()): ?>
                                    <a href="/unassign.php?id=<?= $a['id'] ?>" 
                                       class="btn btn-outline-danger btn-sm"
                                       onclick="return confirm('Unassign this item?')">
                                        <i class="fa fa-undo"></i> Unassign
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">No Access</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
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
    let from = document.getElementById("fromDate").value;
    let to = document.getElementById("toDate").value;

    let rows = document.querySelectorAll("#reportBody tr");

    rows.forEach(row => {
        if(row.cells.length < 5) return; // Skip empty message rows
        
        let rowCat   = row.cells[1].innerText.toLowerCase();
        let rowAsset = row.cells[2].innerText.toLowerCase();
        let rowUser  = row.cells[3].innerText.toLowerCase();
        let rowDateStr = row.cells[5].innerText.trim(); // d-m-Y

        let show = true;
        if (category && !rowCat.includes(category)) show = false;
        if (asset && !rowAsset.includes(asset)) show = false;
        if (user && !rowUser.includes(user)) show = false;
        
        if (from || to) {
            let parts = rowDateStr.split('-');
            let checkDate = `${parts[2]}-${parts[1]}-${parts[0]}`; // Convert to Y-m-d
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
        row.removeChild(row.lastElementChild); // Remove Action Column
    });
    let wb = XLSX.utils.table_to_book(tableClone, { sheet: "Assignments" });
    XLSX.writeFile(wb, filename);
}

async function exportPDF(tableId, filename) {
    const { jsPDF } = window.jspdf;
    let doc = new jsPDF('l', 'mm', 'a4'); 
    doc.text("Inventory Assignments Report", 14, 15);
    doc.autoTable({
        html: "#" + tableId,
        startY: 20,
        theme: "grid",
        columns: [0, 1, 2, 3, 4, 5], // Exclude Action column
        styles: { fontSize: 8 }
    });
    doc.save(filename);
}
</script>

<?php include __DIR__ . '/footer.php'; ?>