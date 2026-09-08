<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers/auth.php';

check_login();
$pdo = db();

/* -------------------------------
    FETCH CATEGORIES
-------------------------------- */
$categories = $pdo->query("
    SELECT id, name 
    FROM rented_categories 
    ORDER BY name ASC
")->fetchAll(PDO::FETCH_ASSOC);

/* -------------------------------
    FETCH PROJECTS (For Filter if needed)
-------------------------------- */
$projects = $pdo->query("SELECT id, project_name FROM rented_projects ORDER BY project_name ASC")->fetchAll(PDO::FETCH_ASSOC);

/* -------------------------------
    FILTERS
-------------------------------- */
$search   = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$project  = $_GET['project'] ?? '';

$sql = "
SELECT r.*, 
       c.name AS category_name,
       p.project_name
FROM rented_inventory r
LEFT JOIN rented_categories c ON r.category_id = c.id
LEFT JOIN rented_projects p ON r.project_id = p.id
WHERE 1
";

$params = [];

if ($search !== '') {
    $sql .= " AND (
        r.product_name LIKE ? OR 
        r.serial_no LIKE ? OR 
        r.vendor_name LIKE ? OR 
        r.company_name LIKE ? OR 
        r.location LIKE ? OR
        p.project_name LIKE ?
    )";
    for ($i = 0; $i < 6; $i++) {
        $params[] = "%$search%";
    }
}

if ($category !== '') {
    $sql .= " AND r.category_id = ?";
    $params[] = $category;
}

if ($project !== '') {
    $sql .= " AND r.project_id = ?";
    $params[] = $project;
}

$sql .= " ORDER BY r.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include __DIR__ . '/header.php'; ?>

<div class="main-content-inner px-4 py-3">

<div class="d-flex justify-content-between mb-3 align-items-center">
    <h3>Rented Inventory List</h3>
    <div>
        <button onclick="exportExcel('inventoryTable', 'Rented_Inventory.xlsx')" class="btn btn-success me-2">
            <i class="fa fa-file-excel"></i> Excel
        </button>
        <button onclick="exportPDF('inventoryTable', 'Rented_Inventory.pdf')" class="btn btn-danger me-2">
            <i class="fa fa-file-pdf"></i> PDF
        </button>
        <a href="/rented_inventory_add.php" class="btn btn-primary">
            + Add Rented Inventory
        </a>
    </div>
</div>

<form class="card p-3 mb-3" method="get">
    <div class="row g-3">
        <div class="col-md-3">
            <input type="text" name="search" class="form-control" 
                   placeholder="Search product, serial, project..." 
                   value="<?= htmlspecialchars($search) ?>">
        </div>

        <div class="col-md-3">
            <select name="category" class="form-control">
                <option value="">All Categories</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= ($category==$c['id'])?'selected':'' ?>>
                        <?= htmlspecialchars($c['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3">
            <select name="project" class="form-control">
                <option value="">All Projects</option>
                <?php foreach ($projects as $pj): ?>
                    <option value="<?= $pj['id'] ?>" <?= ($project==$pj['id'])?'selected':'' ?>>
                        <?= htmlspecialchars($pj['project_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <button class="btn btn-dark w-100">Filter</button>
        </div>
    </div>
</form>

<div class="card">
<div class="card-body p-0">

<table id="inventoryTable" class="table table-bordered table-striped mb-0">
<thead class="table-dark">
<tr>
    <th>ID</th>
    <th>Category</th>
    <th>Project Name</th>
    <th>Product</th>
    <th>Serial</th>
    <th>Qty</th>
    <th>Vendor</th>
    <th>Location</th>
    <th>Date</th>
    <th>Status</th>
    <th>Invoice</th>
    <th width="140">Actions</th>
</tr>
</thead>

<tbody>
<?php if (!$items): ?>
<tr>
    <td colspan="12" class="text-center text-muted py-3">
        No records found
    </td>
</tr>
<?php endif; ?>

<?php foreach ($items as $row): 
    $is_locked = ($row['status'] === 'assigned' || $row['status'] === 'returned');
?>
<tr>
    <td><?= $row['id'] ?></td>
    <td><?= htmlspecialchars($row['category_name'] ?? 'N/A') ?></td>
    <td class="fw-bold text-primary"><?= htmlspecialchars($row['project_name'] ?? 'Unassigned') ?></td>
    <td><?= htmlspecialchars($row['product_name']) ?></td>
    <td><?= htmlspecialchars($row['serial_no']) ?></td>
    <td><?= $row['quantity'] ?></td>
    <td><?= htmlspecialchars($row['vendor_name']) ?></td>
    <td><?= htmlspecialchars($row['location']) ?></td>
    <td><?= $row['purchase_date'] ?></td>

    <td>
        <?php if ($row['status'] === 'assigned'): ?>
            <span class="badge bg-primary">Assigned</span>
        <?php elseif ($row['status'] === 'returned'): ?>
            <span class="badge bg-secondary">Returned</span>
        <?php else: ?>
            <span class="badge bg-success">Available</span>
        <?php endif; ?>
    </td>

    <td class="text-center">
        <?php if ($row['invoice_file']): ?>
            <div class="btn-group">
                <button class="btn btn-sm btn-info" title="Preview"
                    onclick="previewInvoice('<?= $row['invoice_file'] ?>')">
                    <i class="fa fa-eye"></i>
                </button>
                <a class="btn btn-sm btn-primary" title="Download"
                   href="<?= $row['invoice_file'] ?>" download>
                   <i class="fa fa-download"></i>
                </a>
            </div>
        <?php else: ?>
            <button class="btn btn-sm btn-outline-secondary" 
                onclick="openUploadModal(<?= $row['id'] ?>)">
                <i class="fa fa-upload"></i> Upload
            </button>
        <?php endif; ?>
    </td>

    <td>
        <div class="d-flex gap-1">
            <?php if ($is_locked): ?>
                <button class="btn btn-sm btn-success disabled" style="opacity:0.5; cursor:not-allowed;"><i class="fa fa-edit"></i></button>
                <button class="btn btn-sm btn-danger disabled" style="opacity:0.5; cursor:not-allowed;"><i class="fa fa-trash"></i></button>
            <?php else: ?>
                <a href="/rented_inventory_edit.php?id=<?= $row['id'] ?>" 
                   class="btn btn-sm btn-success" title="Edit"><i class="fa fa-edit"></i></a>

                <a href="/itms.arukustech.com/public/api/rented_inventory_delete.php?id=<?= $row['id'] ?>" 
                   class="btn btn-sm btn-danger" title="Delete"
                   onclick="return confirm('Delete this record?')"><i class="fa fa-trash"></i></a>
            <?php endif; ?>
        </div>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

</div>
</div>
</div>

<div class="modal fade" id="uploadInvoiceModal">
<div class="modal-dialog">
<div class="modal-content">
<form id="invoiceUploadForm" enctype="multipart/form-data">
<div class="modal-header">
    <h5>Upload Invoice</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <input type="hidden" name="id" id="upload_id">
    <div class="mb-3">
        <label class="form-label">Select File</label>
        <input type="file" name="invoice" class="form-control" required>
    </div>
</div>
<div class="modal-footer">
    <button class="btn btn-primary">Upload Now</button>
</div>
</form>
</div>
</div>
</div>

<div class="modal fade" id="previewModal">
<div class="modal-dialog modal-xl">
<div class="modal-content">
<div class="modal-header">
    <h5>Invoice Preview</h5>
    <button class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <iframe id="previewFrame" style="width:100%;height:80vh; border:none;"></iframe>
</div>
</div>
</div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script>
// EXPORT EXCEL
function exportExcel(tableId, filename) {
    let table = document.getElementById(tableId);
    let tableClone = table.cloneNode(true);
    let rows = tableClone.querySelectorAll('tr');
    rows.forEach(row => {
        row.removeChild(row.lastElementChild); // Remove Actions
        row.removeChild(row.children[10]); // Remove Invoice column
    });
    let wb = XLSX.utils.table_to_book(tableClone, { sheet: "Rented Inventory" });
    XLSX.writeFile(wb, filename);
}

// EXPORT PDF
async function exportPDF(tableId, filename) {
    const { jsPDF } = window.jspdf;
    let doc = new jsPDF('l', 'mm', 'a4'); 
    doc.text("Rented Inventory Report", 14, 15);
    doc.autoTable({
        html: "#" + tableId,
        startY: 20,
        theme: "grid",
        columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9], // Export up to Status column
        styles: { fontSize: 7 },
        headStyles: { fillColor: [30, 41, 59] }
    });
    doc.save(filename);
}

function openUploadModal(id){
    $('#upload_id').val(id);
    new bootstrap.Modal('#uploadInvoiceModal').show();
}

function previewInvoice(path){
    $('#previewFrame').attr('src', path);
    new bootstrap.Modal('#previewModal').show();
}

$('#invoiceUploadForm').on('submit', function(e){
    e.preventDefault();
    let fd = new FormData(this);
    $.ajax({
        url: '/itms.arukustech.com/public/api/rented_inventory_upload_invoice.php',
        type: 'POST',
        data: fd,
        contentType:false,
        processData:false,
        success: function(res){
            try {
                let r = JSON.parse(res);
                if(r.status === 'success'){
                    location.reload();
                } else {
                    alert(r.message);
                }
            } catch(e) {
                location.reload(); // Fallback if response isn't pure JSON
            }
        }
    });
});
</script>

<?php include __DIR__ . '/footer.php'; ?>