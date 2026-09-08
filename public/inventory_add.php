<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers/auth.php';

check_login();
$pdo = db();

/* LOAD CATEGORIES */
$cats = $pdo->query("
    SELECT id, name, asset_prefix 
    FROM inv_categories 
    ORDER BY name ASC
")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/layout_top_inventory.php';
?>

<h3 class="mb-4 fw-bold">Add Inventory Item</h3>

<div class="card">
<div class="card-body">

<!-- IMPORTANT: enctype is REQUIRED -->
<form id="addForm" enctype="multipart/form-data">

<div class="row g-3">

    <div class="col-md-4">
        <label class="form-label">Category *</label>
        <select name="category_id" class="form-select" required>
            <option value="">Select</option>
            <?php foreach ($cats as $cat): ?>
                <option value="<?= $cat['id'] ?>">
                    <?= htmlspecialchars($cat['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label">Item Details *</label>
        <input type="text" name="item_details" class="form-control" required>
    </div>

    <div class="col-md-4">
        <label class="form-label">Brand</label>
        <input type="text" name="brand" class="form-control">
    </div>

    <div class="col-md-4">
        <label class="form-label">Model</label>
        <input type="text" name="model" class="form-control">
    </div>

    <div class="col-md-4">
        <label class="form-label">Serial Number</label>
        <input type="text" name="serial_no" class="form-control">
    </div>

    <div class="col-md-4">
        <label class="form-label">Purchase Date</label>
        <input type="date" name="purchase_date" class="form-control">
    </div>

    <div class="col-md-4">
        <label class="form-label">Invoice No.</label>
        <input type="text" name="invoice_no" class="form-control">
    </div>

    <div class="col-md-4">
        <label class="form-label">Warranty End Date</label>
        <input type="date" name="warranty_date" class="form-control">
    </div>

    <div class="col-md-4">
        <label class="form-label">Cost</label>
        <input type="number" name="cost" class="form-control">
    </div>

    <!-- OPTIONAL FILE UPLOAD -->
    <div class="col-md-6">
        <label class="form-label">Upload Invoice (Optional)</label>
        <input 
            type="file" 
            name="invoice_file" 
            class="form-control"
            accept="application/pdf,image/*"
        >
    </div>

    <div class="col-12">
        <button type="submit" class="btn btn-primary mt-3">
            Add Item
        </button>
    </div>

</div>
</form>

</div>
</div>

<!-- IMPORTANT: jQuery must be loaded -->
<script>
$('#addForm').on('submit', function(e) {
    e.preventDefault();

    let formData = new FormData(this);

    $.ajax({
        url: '/itms.arukustech.com/public/api/inventory_add.php',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        dataType: 'json',

        success: function(res) {
            if (res.status === "success") {
                alert(
                    "Item added successfully!\nAsset Code: " + res.asset_code
                );
                $('#addForm')[0].reset();
            } else {
                alert("ERROR: " + res.message);
            }
        },

        error: function(xhr) {
            console.error(xhr.responseText);
            alert("API error while adding item.");
        }
    });
});
</script>

<?php include __DIR__ . '/layout_bottom_inventory.php'; ?>
