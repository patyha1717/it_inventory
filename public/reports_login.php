<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers/auth.php';
auth();

$pdo = db();

// Fetch login logs with username
$rows = $pdo->query("
    SELECT 
        l.id,
        u.name AS username,
        l.action,
        l.ip_address,
        l.user_agent,
        l.created_at
    FROM login_logs l
    LEFT JOIN users u ON l.user_id = u.id
    ORDER BY l.id DESC
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

<div class="container mt-4">
    <h3 class="mb-3">Login Activity Report</h3>

    <!-- FILTERS -->
    <div class="filter-box mb-3">
        <div class="row g-3">
            <div class="col-md-3">
                <label>From Date</label>
                <input type="date" id="fromDate" class="form-control">
            </div>

            <div class="col-md-3">
                <label>To Date</label>
                <input type="date" id="toDate" class="form-control">
            </div>

            <div class="col-md-4">
                <label>Search</label>
                <input type="text" id="searchBox" placeholder="Search user, action, IP..." class="form-control">
            </div>

            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-secondary w-100" onclick="resetFilters()">Reset</button>
            </div>
        </div>
    </div>

    <!-- EXPORT BUTTONS -->
    <div class="mb-3">
        <button class="btn btn-success btn-sm"
            onclick="exportExcel('reportTable', 'login_logs.xlsx')">Export Excel</button>

        <button class="btn btn-danger btn-sm"
            onclick="exportPDF('reportTable', 'login_logs.pdf')">Export PDF</button>
    </div>

    <table id="reportTable" class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Action</th>
                <th>IP</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody id="reportBody">
            <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= $r['id'] ?></td>
                <td><?= $r['username'] ?></td>
                <td><?= $r['action'] ?></td>
                <td><?= $r['ip_address'] ?></td>
                <td><?= $r['created_at'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
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

    doc.text("Login Logs Report", 14, 10);

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
        let date = row.children[4].innerText;
        let text = row.innerText.toLowerCase();

        let show = true;

        // Date filter
        if (from && date < from) show = false;
        if (to && date > to) show = false;

        // Search box filter
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
