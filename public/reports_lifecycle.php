<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers/auth.php';

check_login();
$pdo = db();

$items = $pdo->query("
    SELECT *,
        CASE 
            WHEN warranty_date < CURDATE() THEN 'Expired'
            WHEN DATEDIFF(warranty_date, CURDATE()) < 60 THEN 'Expiring Soon'
            ELSE 'Active'
        END AS warranty_status
    FROM inventory_items
    ORDER BY warranty_date ASC
")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/header.php';
?>

<style>
.table thead th {
    background:#1e293b !important;
    color:white !important;
}
.filter-box {
    background: #f8fafc;
    padding: 15px;
    border-radius: 8px;
    border: 1px solid #ddd;
}
</style>

<!-- <div class="container mt-4"> -->
<div class="main-content-inner px-4 py-3 d-block w-100">

<div class="container-fluid">
    <h3 class="mb-3">Asset Lifecycle / Warranty Report</h3>

    <!-- FILTERS -->
    <div class="filter-box mb-3">
        <div class="row g-3">
            <div class="col-md-3">
                <label>Warranty From</label>
                <input type="date" id="fromDate" class="form-control">
            </div>

            <div class="col-md-3">
                <label>Warranty To</label>
                <input type="date" id="toDate" class="form-control">
            </div>

            <div class="col-md-4">
                <label>Search</label>
                <input type="text" id="searchBox" placeholder="Search item, brand, model, status..." class="form-control">
            </div>

            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-secondary w-100" onclick="resetFilters()">Reset</button>
            </div>
        </div>
    </div>

    <!-- EXPORT BUTTONS -->
    <div class="mb-3">
        <button class="btn btn-success btn-sm"
            onclick="exportExcel('reportTable', 'lifecycle_report.xlsx')">Export Excel</button>

        <button class="btn btn-danger btn-sm"
            onclick="exportPDF('reportTable', 'lifecycle_report.pdf')">Export PDF</button>
    </div>

    <!-- REPORT TABLE -->
    <table id="reportTable" class="table table-bordered table-striped">
        <thead>
        <tr>
            <th>Item</th>
            <th>Brand</th>
            <th>Model</th>
            <th>Warranty Till</th>
            <th>Status</th>
        </tr>
        </thead>

        <tbody id="reportBody">
        <?php foreach ($items as $i): ?>
            <tr>
                <td><?= htmlspecialchars($i['item_details']) ?></td>
                <td><?= $i['brand'] ?></td>
                <td><?= $i['model'] ?></td>
                <td><?= $i['warranty_date'] ?></td>

                <td>
                    <?php if ($i['warranty_status'] == 'Expired'): ?>
                        <span class="badge bg-danger"><?= $i['warranty_status'] ?></span>
                    <?php elseif ($i['warranty_status'] == 'Expiring Soon'): ?>
                        <span class="badge bg-warning text-dark"><?= $i['warranty_status'] ?></span>
                    <?php else: ?>
                        <span class="badge bg-success"><?= $i['warranty_status'] ?></span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
                    </div>

<!-- EXPORT LIBRARIES -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script>
// ---------------- EXPORT EXCEL ----------------
function exportExcel(tableId, filename = "report.xlsx") {
    let table = document.getElementById(tableId);
    let wb = XLSX.utils.table_to_book(table, { sheet: "Report" });
    XLSX.writeFile(wb, filename);
}

// ---------------- EXPORT PDF ----------------
async function exportPDF(tableId, filename = "report.pdf") {
    const { jsPDF } = window.jspdf;
    let doc = new jsPDF();

    doc.text("Asset Lifecycle / Warranty Report", 14, 10);

    doc.autoTable({
        html: "#" + tableId,
        startY: 15,
        theme: "grid",
    });

    doc.save(filename);
}

// ---------------- FILTER FUNCTION ----------------
function applyFilters() {
    let from = document.getElementById("fromDate").value;
    let to = document.getElementById("toDate").value;
    let search = document.getElementById("searchBox").value.toLowerCase();

    let rows = document.querySelectorAll("#reportBody tr");

    rows.forEach(row => {
        let warranty = row.children[3].innerText;
        let text = row.innerText.toLowerCase();

        let show = true;

        // Date filter
        if (from && warranty < from) show = false;
        if (to && warranty > to) show = false;

        // Search filter
        if (search && !text.includes(search)) show = false;

        row.style.display = show ? "" : "none";
    });
}

document.getElementById("fromDate").addEventListener("change", applyFilters);
document.getElementById("toDate").addEventListener("change", applyFilters);
document.getElementById("searchBox").addEventListener("keyup", applyFilters);

function resetFilters() {
    document.getElementById("fromDate").value = "";
    document.getElementById("toDate").value = "";
    document.getElementById("searchBox").value = "";
    applyFilters();
}
</script>

<?php include __DIR__ . '/footer.php'; ?>
