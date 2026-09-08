<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers/auth.php';
auth();

$pdo = db();

/* Fetch brand-wise inventory summary */
$sql = "
    SELECT 
        i.brand AS brand,
        COUNT(i.id) AS total,
        SUM(i.status = 'available') AS available,
        SUM(i.status = 'assigned') AS assigned,
        SUM(i.status = 'faulty') AS faulty,
        SUM(i.status = 'missing') AS missing,
        SUM(i.status = 'maintenance') AS maintenance,
        SUM(i.status = 'retired') AS retired
    FROM inventory_items i
    GROUP BY i.brand
    HAVING brand IS NOT NULL AND brand <> ''
    ORDER BY brand ASC
";

$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/header.php';
?>

<div class="main-content-inner px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold m-0">Brand Wise Asset Report</h3>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-success d-flex align-items-center gap-2" onclick="exportExcel('brandTable', 'Brand_Asset_Report.xlsx')">
                <i class="ri-file-excel-2-line"></i> Export Excel
            </button>
            <button type="button" class="btn btn-danger d-flex align-items-center gap-2" onclick="exportPDF('brandTable', 'Brand_Asset_Report.pdf')">
                <i class="ri-file-pdf-line"></i> Export PDF
            </button>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <table id="brandTable" class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-3">Brand Name</th>
                        <th>Total Assets</th>
                        <th>Available</th>
                        <th>Assigned</th>
                        <th>Faulty</th>
                        <th>Missing</th>
                        <th>Maintenance</th>
                        <th>Retired</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="8" class="text-center py-3">No data found.</td></tr>
                    <?php endif; ?>
                    
                    <?php foreach ($rows as $r): ?>
                    <tr>
                        <td class="ps-3 fw-bold"><?= htmlspecialchars($r['brand']) ?></td>
                        <td><span class="badge bg-light text-dark border"><?= $r['total'] ?></span></td>
                        <td><span class="text-success fw-bold"><?= $r['available'] ?></span></td>
                        <td><span class="text-primary fw-bold"><?= $r['assigned'] ?></span></td>
                        <td><span class="text-danger fw-bold"><?= $r['faulty'] ?></span></td>
                        <td><span class="text-info fw-bold"><?= $r['missing'] ?></span></td>
                        <td><span class="text-warning fw-bold"><?= $r['maintenance'] ?></span></td>
                        <td><span class="text-secondary fw-bold"><?= $r['retired'] ?></span></td>
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
// EXPORT EXCEL FUNCTION
function exportExcel(tableId, filename) {
    let table = document.getElementById(tableId);
    let wb = XLSX.utils.table_to_book(table, { sheet: "Brand Summary" });
    XLSX.writeFile(wb, filename);
}

// EXPORT PDF FUNCTION
async function exportPDF(tableId, filename) {
    const { jsPDF } = window.jspdf;
    let doc = new jsPDF('l', 'mm', 'a4'); // Landscape mode
    
    doc.setFontSize(16);
    doc.text("Brand Wise Asset Report", 14, 15);
    doc.setFontSize(10);
    doc.text("Generated on: " + new Date().toLocaleString(), 14, 22);

    doc.autoTable({
        html: "#" + tableId,
        startY: 28,
        theme: "grid",
        headStyles: { fillColor: [30, 41, 59] }, // Matching table-dark
        styles: { fontSize: 9, cellPadding: 3 }
    });
    
    doc.save(filename);
}

// Initialize simple DataTable for sorting/search (No Buttons extension needed)
$(document).ready(function() {
    $('#brandTable').DataTable({
        "pageLength": 20,
        "language": {
            "search": "Search Brand:"
        }
    });
});
</script>

<?php include __DIR__ . '/footer.php'; ?>