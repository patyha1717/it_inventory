<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers/auth.php';

check_login();
$pdo = db();

$data = $pdo->query("
    SELECT id, item_details, brand, model, cost, purchase_date,
        ROUND((DATEDIFF(CURDATE(), purchase_date)/365)* (cost/5), 2) AS depreciation,
        ROUND(cost - ((DATEDIFF(CURDATE(), purchase_date)/365)* (cost/5)), 2) AS current_value
    FROM inventory_items
    WHERE cost IS NOT NULL AND purchase_date IS NOT NULL
    ORDER BY purchase_date DESC
")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/header.php';
?>

<style>
.table thead th {
    background:#1e293b !important;
    color:white !important;
}
.filter-box {
    background:#f8fafc;
    padding:15px;
    border-radius:6px;
    border:1px solid #ddd;
}
</style>

<!-- <div class="container mt-4"> -->
<div class="main-content-inner px-4 py-3 d-block w-100">

<div class="container-fluid">

    <h3 class="mb-3">Asset Depreciation Report</h3>

    <!-- FILTER SECTION -->
    <div class="filter-box mb-3">
        <div class="row g-3">

            <div class="col-md-3">
                <label>Purchase From</label>
                <input type="date" id="fromDate" class="form-control">
            </div>

            <div class="col-md-3">
                <label>Purchase To</label>
                <input type="date" id="toDate" class="form-control">
            </div>

            <div class="col-md-4">
                <label>Search</label>
                <input type="text" id="searchBox" class="form-control" placeholder="Search item, brand, model...">
            </div>

            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-secondary w-100" onclick="resetFilters()">Reset</button>
            </div>

        </div>
    </div>

    <!-- EXPORT BUTTONS -->
    <div class="mb-3">
        <button class="btn btn-success btn-sm" onclick="exportExcel('reportTable','depreciation_report.xlsx')">Export Excel</button>
        <button class="btn btn-danger btn-sm" onclick="exportPDF('reportTable','depreciation_report.pdf')">Export PDF</button>
    </div>

    <!-- REPORT TABLE -->
    <table id="reportTable" class="table table-bordered table-striped">
        <thead>
        <tr>
            <th>Item</th>
            <th>Brand</th>
            <th>Model</th>
            <th>Cost</th>
            <th>Purchase Date</th>
            <th>Depreciation</th>
            <th>Current Value</th>
        </tr>
        </thead>

        <tbody id="reportBody">
        <?php foreach ($data as $d): ?>
            <tr>
                <td><?= htmlspecialchars($d['item_details']) ?></td>
                <td><?= $d['brand'] ?></td>
                <td><?= $d['model'] ?></td>
                <td><?= $d['cost'] ?></td>
                <td><?= $d['purchase_date'] ?></td>
                <td><?= $d['depreciation'] ?></td>
                <td><?= $d['current_value'] ?></td>
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
function exportExcel(tableId, filename="export.xlsx") {
    let table = document.getElementById(tableId);
    let wb = XLSX.utils.table_to_book(table, { sheet: "Report" });
    XLSX.writeFile(wb, filename);
}

// ---------------- EXPORT PDF ----------------
function exportPDF(tableId, filename="report.pdf") {
    const { jsPDF } = window.jspdf;
    let doc = new jsPDF();

    doc.text("Asset Depreciation Report", 14, 10);

    doc.autoTable({
        html: "#" + tableId,
        startY: 15,
        theme: "grid"
    });

    doc.save(filename);
}

// ---------------- FILTER LOGIC ----------------
function applyFilters() {
    let from = document.getElementById("fromDate").value;
    let to = document.getElementById("toDate").value;
    let search = document.getElementById("searchBox").value.toLowerCase();

    document.querySelectorAll("#reportBody tr").forEach(row => {
        let purchaseDate = row.children[4].innerText.trim();
        let rowText = row.innerText.toLowerCase();

        let show = true;

        if (from && purchaseDate < from) show = false;
        if (to && purchaseDate > to) show = false;
        if (search && !rowText.includes(search)) show = false;

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
