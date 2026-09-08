<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers/auth.php';

check_login();
$pdo = db();

# Category summary
$cat = $pdo->query("
    SELECT c.name,
        COUNT(i.id) AS total,
        SUM(CASE WHEN i.status='available' THEN 1 ELSE 0 END) AS available,
        SUM(CASE WHEN i.status='assigned' THEN 1 ELSE 0 END) AS assigned,
        SUM(CASE WHEN i.status='maintenance' THEN 1 ELSE 0 END) AS maintenance,
        SUM(CASE WHEN i.status='retired' THEN 1 ELSE 0 END) AS retired
    FROM inv_categories c
    LEFT JOIN inventory_items i ON i.category_id = c.id
    GROUP BY c.id
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

    <h3 class="mb-3">Inventory Usage Report</h3>

    <!-- FILTER BOX -->
    <div class="filter-box mb-3">
        <div class="row g-3">

            <div class="col-md-6">
                <label>Search Category</label>
                <input type="text" id="searchBox" class="form-control" placeholder="Search category...">
            </div>

            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-secondary w-100" onclick="resetFilters()">Reset</button>
            </div>

        </div>
    </div>

    <!-- EXPORT BUTTONS -->
    <div class="mb-3">
        <button class="btn btn-success btn-sm" onclick="exportExcel('usageTable','usage_report.xlsx')">Export Excel</button>
        <button class="btn btn-danger btn-sm" onclick="exportPDF('usageTable','usage_report.pdf')">Export PDF</button>
    </div>

    <!-- REPORT TABLE -->
    <table id="usageTable" class="table table-bordered table-striped">
        <thead>
        <tr>
            <th>Category</th>
            <th>Total</th>
            <th>Available</th>
            <th>Assigned</th>
            <th>Maintenance</th>
            <th>Retired</th>
        </tr>
        </thead>
        <tbody id="usageBody">
        <?php foreach ($cat as $c): ?>
            <tr>
                <td><?= htmlspecialchars($c['name']) ?></td>
                <td><?= $c['total'] ?></td>
                <td><?= $c['available'] ?></td>
                <td><?= $c['assigned'] ?></td>
                <td><?= $c['maintenance'] ?></td>
                <td><?= $c['retired'] ?></td>
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

    doc.text("Inventory Usage Report", 14, 10);

    doc.autoTable({
        html: "#" + tableId,
        startY: 15,
        theme: "grid"
    });

    doc.save(filename);
}

// ---------------- FILTER LOGIC ----------------
function applyFilters() {
    let search = document.getElementById("searchBox").value.toLowerCase();

    document.querySelectorAll("#usageBody tr").forEach(row => {
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(search) ? "" : "none";
    });
}

document.getElementById("searchBox").addEventListener("keyup", applyFilters);

function resetFilters() {
    document.getElementById("searchBox").value = "";
    applyFilters();
}
</script>

<?php include __DIR__ . '/footer.php'; ?>
