<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers/auth.php';

check_login();

$id = intval($_GET['id']);
$pdo = db();

// Fetch item
$item = $pdo->prepare("SELECT * FROM inventory_items WHERE id=?");
$item->execute([$id]);
$item = $item->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    die("Item not found.");
}

// Fetch categories
$cats = $pdo->query("SELECT id, name, asset_prefix FROM inv_categories ORDER BY name ASC")
            ->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/layout_top_inventory.php';
?>

<h3 class="mb-4 fw-bold">Edit Inventory Item</h3>

<div class="card">
<div class="card-body">

<form action="/itms.arukustech.com/public/api/inventory_edit.php" method="POST" enctype="multipart/form-data">

<input type="hidden" name="id" value="<?= $item['id'] ?>">

<div class="row g-3">

    <!-- Category -->
    <div class="col-md-4">
        <label class="form-label">Category *</label>
        <select name="category_id" class="form-select" required>
            <?php foreach ($cats as $cat): ?>
                <option value="<?= $cat['id'] ?>"
                    <?= $cat['id'] == $item['category_id'] ? "selected" : "" ?>>
                    <?= htmlspecialchars($cat['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Asset Code (READ ONLY) -->
    <div class="col-md-4">
        <label class="form-label">Asset Code</label>
        <input type="text" class="form-control" value="<?= htmlspecialchars($item['asset_code']) ?>" readonly>
    </div>

    <!-- Item Name -->
    <div class="col-md-4">
        <label class="form-label">Item Details *</label>
        <input type="text" name="item_details" class="form-control"
               value="<?= htmlspecialchars($item['item_details']) ?>" required>
    </div>

    <!-- Brand -->
    <div class="col-md-4">
        <label class="form-label">Brand</label>
        <input type="text" name="brand" class="form-control"
               value="<?= htmlspecialchars($item['brand']) ?>">
    </div>

    <!-- Model -->
    <div class="col-md-4">
        <label class="form-label">Model</label>
        <input type="text" name="model" class="form-control"
               value="<?= htmlspecialchars($item['model']) ?>">
    </div>

    <!-- Serial -->
    <div class="col-md-4">
        <label class="form-label">Serial Number</label>
        <input type="text" name="serial_no" class="form-control"
               value="<?= htmlspecialchars($item['serial_no']) ?>">
    </div>

    <!-- Purchase -->
    <div class="col-md-4">
        <label class="form-label">Purchase Date</label>
        <input type="date" name="purchase_date" class="form-control"
               value="<?= $item['purchase_date'] ?>">
    </div>

    <!-- Invoice -->
    <div class="col-md-4">
        <label class="form-label">Invoice No.</label>
        <input type="text" name="invoice_no" class="form-control"
               value="<?= htmlspecialchars($item['invoice_no']) ?>">
    </div>

    <!-- Warranty -->
    <div class="col-md-4">
        <label class="form-label">Warranty End Date</label>
        <input type="date" name="warranty_date" class="form-control"
               value="<?= $item['warranty_date'] ?>">
    </div>

    <!-- Cost -->
    <div class="col-md-4">
        <label class="form-label">Cost</label>
        <input type="number" name="cost" class="form-control"
               value="<?= $item['cost'] ?>">
    </div>
     
    <div class="col-md-4">
        <label class="form-label">Status *</label>
        <select name="status" class="form-select" required <?= $item['status'] == 'assigned' ? 'disabled' : ' ' ?>>
            <option value="available" <?= $item['status'] == 'available' ? 'selected' : '' ?>>Available</option>
            <option value="maintenance" <?= $item['status'] == 'maintenance' ? 'selected' : '' ?>>Under Maintenance</option>
            <option value="faulty" <?= $item['status'] == 'faulty' ? 'selected' : '' ?>>Faulty</option>
            <option value="missing" <?= $item['status'] == 'missing' ? 'selected' : '' ?>>Missing</option>
            <option value="retired" <?= $item['status'] == 'retired' ? 'selected' : '' ?>>Retired</option>
            <?php if($item['status'] == 'assigned'): ?>
                <option value="assigned" selected>Assigned (Locked)</option>
            <?php endif; ?>
        </select>
        <?php if($item['status'] == 'assigned'): ?>
            <input type="hidden" name="status" value="assigned">
            <small class="text-danger d-block">Unassign item to change status.</small>
        <?php endif; ?>
    </div>

    <!-- INVOICE FILE UPLOAD SECTION -->
    <div class="col-md-12 mt-3">
        <label class="form-label fw-bold">Invoice File (PDF/JPG/PNG)</label>
        
        <?php if (!empty($item['invoice_file'])): ?>
            <div class="mb-2">

                <!-- Preview -->
                <button type="button" class="btn btn-info btn-sm"
                        onclick="previewInvoice('<?= $item['invoice_file'] ?>')">
                    Preview
                </button>

                <!-- Download -->
                <a href="<?= $item['invoice_file'] ?>" download class="btn btn-primary btn-sm">
                    Download
                </a>

                <!-- Replace -->
                <span class="badge bg-warning text-dark">You can upload to replace existing file</span>
            </div>
        <?php endif; ?>

        <input type="file" name="invoice_file" class="form-control">
    </div>


    <!-- Submit -->
    <div class="col-12">
        <button class="btn btn-success mt-3">Update Item</button>
    </div>

</div>
</form>

</div>
</div>


<!-- MODAL FOR PREVIEW -->
<div class="modal fade" id="invoicePreviewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
        
            <div class="modal-header">
                <h5 class="modal-title">Invoice Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <iframe id="invoicePreviewFrame" style="width:100%; height:80vh;"></iframe>
            </div>

        </div>
    </div>
</div>

<script>
function previewInvoice(path) {
    document.getElementById("invoicePreviewFrame").src = path;
    new bootstrap.Modal(document.getElementById("invoicePreviewModal")).show();
}
</script>

<?php include __DIR__ . '/layout_bottom_inventory.php'; ?>
