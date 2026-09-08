<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers/auth.php';

check_login();

$pdo = db();

/* -------------------------------
    FETCH FILTER OPTIONS
-------------------------------- */
$categories = $pdo->query("SELECT id, name FROM inv_categories ORDER BY name ASC")
                  ->fetchAll(PDO::FETCH_ASSOC);

/* -------------------------------
    READ FILTER VALUES
-------------------------------- */
$search     = isset($_GET['search']) ? trim($_GET['search']) : "";
$category   = isset($_GET['category']) ? intval($_GET['category']) : "";
$status     = isset($_GET['status']) ? trim($_GET['status']) : "";

/* -------------------------------
    BUILD FILTER QUERY
-------------------------------- */
// Fix: Use the inventory_assignments table to link items to users
$sql = "
SELECT i.*, 
       c.name AS category_name,
       u.name AS assigned_to_user
FROM inventory_items i
LEFT JOIN inv_categories c ON i.category_id = c.id
LEFT JOIN inventory_assignments ia ON i.id = ia.item_id AND ia.unassigned_on IS NULL
LEFT JOIN users u ON ia.user_id = u.id
WHERE 1
";

$params = [];

// Search filter
if ($search !== "") {
    $sql .= " AND (
                i.item_deatils LIKE ? OR 
                i.brand LIKE ? OR 
                i.model LIKE ? OR 
                i.serial_no LIKE ? OR
                i.asset_code LIKE ?
            )";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Category filter
if ($category !== "") {
    $sql .= " AND i.category_id = ?";
    $params[] = $category;
}

// Status filter
if ($status !== "") {
    $sql .= " AND i.status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY i.id DESC";

$st = $pdo->prepare($sql);
$st->execute($params);

$items = $st->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Inventory List</title>
    <link href="/itms.arukustech.com/public/assets/css/bootstrap.min.css" rel="stylesheet">
    <script src="/itms.arukustech.com/public/assets/js/jquery.min.js"></script>
    <script src="/itms.arukustech.com/public/assets/js/bootstrap.bundle.min.js"></script>
</head>

<body>
<?php include __DIR__ . '/header.php'; ?>

<div class="main-content-inner px-4 py-3">

    <div class="d-flex justify-content-between mb-3 align-items-center">
        <h3>Inventory Items</h3>

        <div>
            <button class="btn btn-success btn-sm" onclick="exportExcel('inventoryTable','inventory_list.xlsx')">Export Excel</button>
            <button class="btn btn-danger btn-sm" onclick="exportPDF('inventoryTable','inventory_list.pdf')">Export PDF</button>

            <?php if (is_admin()): ?>
                <a href="/inventory_add.php" class="btn btn-primary btn-sm ms-2">+ Add Item</a>
            <?php endif; ?>
        </div>
    </div>

    <form method="GET" class="card p-3 mb-3">
        <div class="row g-3">

            <div class="col-md-4">
                <input type="text" name="search" class="form-control"
                       placeholder="Search item, model, brand, serial, asset code..."
                       value="<?= htmlspecialchars($search) ?>">
            </div>

            <div class="col-md-3">
                <select name="category" class="form-control">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>"
                            <?= ($category == $c['id']) ? "selected" : "" ?>>
                            <?= htmlspecialchars($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <select name="status" class="form-control">
                <option value="">All Status</option>
    		<option value="available"   <?= ($status=="available") ? "selected" : "" ?>>Available</option>
  		<option value="assigned"    <?= ($status=="assigned") ? "selected" : "" ?>>Assigned</option>
                <option value="maintenance" <?= ($status=="maintenance") ? "selected" : "" ?>>Maintenance</option>
                <option value="faulty"      <?= ($status=="faulty") ? "selected" : "" ?>>Faulty</option>
                <option value="missing"     <?= ($status=="missing") ? "selected" : "" ?>>Missing</option>
                <option value="retired"     <?= ($status=="retired") ? "selected" : "" ?>>Retired</option>
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
                        <th>SR#</th>
                        <th>Category</th>
                        <th>Item Details</th>
                        <th>Brand</th>
                        <th>Model</th>
                        <th>Serial</th>
                        <th>Asset Code</th>
                        <th>Status</th>
                        <th>Assigned To</th>
                        <th class="no-export">Invoice</th>
                        <th width="180" class="no-export">Actions</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="11" class="text-center py-3 text-muted">
                            No items found.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php 
   		 // 1. Initialize the counter before the loop starts
   		    $sr_no = 1; 
   		    foreach ($items as $row): 
   		    ?>
                   <tr>
                        <td><?= $sr_no++ ?></td>     
                        <td><?= htmlspecialchars($row['category_name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['item_deatils']) ?></td>
                        <td><?= htmlspecialchars($row['brand']) ?></td>
                        <td><?= htmlspecialchars($row['model']) ?></td>
                        <td><?= htmlspecialchars($row['serial_no']) ?></td>
                        <td><?= htmlspecialchars($row['asset_code']) ?></td>

                        <td>
                            <?php if ($row['status'] == 'available'): ?>
     			    <span class="badge bg-success">Available</span>
                            <?php elseif ($row['status'] == 'assigned'): ?>
                            <span class="badge bg-primary">Assigned</span>
                            <?php elseif ($row['status'] == 'maintenance'): ?>
                            <span class="badge bg-warning text-dark">Maintenance</span>
                            <?php elseif ($row['status'] == 'faulty'): ?>
                            <span class="badge bg-danger">Faulty</span>
                            <?php elseif ($row['status'] == 'missing'): ?>
                            <span class="badge bg-dark">Missing</span>
                            <?php elseif ($row['status'] == 'retired'): ?>
                            <span class="badge bg-danger">Retired</span>
                            <?php else: ?>
        		    <span class="badge bg-secondary"><?= htmlspecialchars($row['status']) ?></span>
    			    <?php endif; ?>
                        </td>

                        <td>
                            <?= !empty($row['assigned_to_user']) ? htmlspecialchars($row['assigned_to_user']) : '<span class="text-muted">�</span>' ?>
                        </td>

                        <td class="no-export">
                            <?php if (!empty($row['invoice_file'])): ?>

                                <button class="btn btn-sm btn-info"
                                    onclick="previewInvoice('<?= $row['invoice_file'] ?>')">
                                    Preview
                                </button>

                                <a href="<?= $row['invoice_file'] ?>" download
                                   class="btn btn-sm btn-primary">
                                    Download
                                </a>

                                <button class="btn btn-sm btn-warning"
                                    onclick="openUploadModal(<?= $row['id'] ?>)">
                                    Replace
                                </button>

                            <?php else: ?>

                                <button class="btn btn-sm btn-secondary"
                                    onclick="openUploadModal(<?= $row['id'] ?>)">
                                    Upload
                                </button>

                            <?php endif; ?>
                        </td>

                        <td class="no-export">
    			<?php if (is_admin()): ?>
        		<?php if ($row['status'] !== 'assigned'): ?>
           		 <a href="/inventory_edit.php?id=<?= $row['id'] ?>" 
              		 class="btn btn-sm btn-success">Edit</a>
            
           		 <?php if (is_superadmin()): ?>
               		  <a href="/itms.arukustech.com/public/api/inventory_delete.php?id=<?= $row['id'] ?>"
                  	  class="btn btn-sm btn-danger"
                   	 onclick="return confirm('Delete this item?')">Delete</a>
          		  <?php endif; ?>
        		<?php else: ?>
          		  <span class="badge bg-secondary">Locked</span>
       			 <?php endif; ?>
    			<?php else: ?>
        		<span class="text-muted">No Access</span>
    			<?php endif; ?>
			</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>

            </table>
        </div>
    </div>

</div>

<div class="modal fade" id="uploadInvoiceModal" tabindex="-1">
  <div class="modal-dialog">
      <div class="modal-content">

          <form id="invoiceUploadForm" enctype="multipart/form-data">

              <div class="modal-header">
                  <h5 class="modal-title">Upload Invoice</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>

              <div class="modal-body">
                  <input type="hidden" name="item_id" id="upload_item_id">

                  <div class="mb-3">
                      <label class="form-label">Invoice File (PDF/JPG/PNG)</label>
                      <input type="file" name="invoice_file" class="form-control" required>
                  </div>
              </div>

              <div class="modal-footer">
                  <button class="btn btn-primary">Upload</button>
              </div>

          </form>

      </div>
  </div>
</div>

<div class="modal fade" id="invoicePreviewModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
      <div class="modal-content">

          <div class="modal-header">
              <h5 class="modal-title">Invoice Preview</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>

          <div class="modal-body">
              <iframe id="invoicePreviewFrame" src="" style="width:100%; height:80vh;"></iframe>
          </div>

      </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script>
function exportExcel(tableId, filename="export.xlsx") {
    let table = document.getElementById(tableId);
    let clone = table.cloneNode(true);
    clone.querySelectorAll('.no-export').forEach(el => el.remove());
    let wb = XLSX.utils.table_to_book(clone, { sheet: "Inventory" });
    XLSX.writeFile(wb, filename);
}

function exportPDF(tableId, filename="report.pdf") {
    const { jsPDF } = window.jspdf;
    let doc = new jsPDF('l', 'mm', 'a4'); 
    doc.text("Inventory Items Report", 14, 10);
    doc.autoTable({
        html: "#" + tableId,
        startY: 15,
        theme: "grid",
        didParseCell: function(data) {
            if (data.cell.raw && data.cell.raw.classList && data.cell.raw.classList.contains('no-export')) {
                data.cell.text = '';
            }
        },
        columnStyles: { 9: { cellWidth: 0 }, 10: { cellWidth: 0 } } 
    });
    doc.save(filename);
}

function previewInvoice(path) {
    document.getElementById("invoicePreviewFrame").src = path;
    new bootstrap.Modal(document.getElementById("invoicePreviewModal")).show();
}

function openUploadModal(id) {
    document.getElementById("upload_item_id").value = id;
    new bootstrap.Modal(document.getElementById("uploadInvoiceModal")).show();
}

$("#invoiceUploadForm").on("submit", function (e) {
    e.preventDefault();

    let formData = new FormData(this);

    $.ajax({
        url: "/itms.arukustech.com/public/api/inventory_upload_invoice.php",
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,

        success: function (res) {
            if (res.status === "success") {
                alert("Invoice uploaded successfully!");
                location.reload();
            } else {
                alert("Error: " + res.message);
            }
        },

        error: function () {
            alert("Upload failed.");
        }
    });
});
</script>

</body>
<?php include __DIR__ . '/footer.php'; ?>
</html>