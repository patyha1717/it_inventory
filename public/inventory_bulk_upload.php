<?php
// DEBUG ENABLED
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers/auth.php';

check_login();
include __DIR__ . '/layout_top_inventory.php';
?>

 <div class="main-content-inner px-4 py-3">
<h3 class="mb-4 fw-bold">Bulk Upload Inventory Items</h3>

<div class="card">
<div class="card-body">

<form id="bulkUploadForm" enctype="multipart/form-data">

    <div class="row mb-3">
        <div class="col-md-10">
            <label class="form-label fw-bold">Upload Excel File</label>
            <input type="file" name="excel_file" class="form-control" accept=".xlsx" required>
        </div>

        <div class="col-md-2 text-end">
            <label class="form-label">&nbsp;</label><br>
            <a href="/sample/inventory_sample.xlsx" class="btn btn-outline-primary w-100">
                Sample Excel
            </a>
        </div>
    </div>

    <!-- NEW : COMMON INVOICE FILE -->
    <div class="row mb-3">
        <div class="col-md-12">
            <label class="form-label fw-bold">Upload Common Invoice (Optional)</label>
            <input type="file" name="invoice_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-25">Upload</button>

    <p class="mt-3 text-muted">
        Excel Format:<br>
        <code>
            category_name, item_deatils, brand, model, serial_no, purchase_date, invoice_no, warranty_end, cost
        </code>
    </p>

</form>

</div>
</div>
</div>

<!-- SweetAlert -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.getElementById("bulkUploadForm").addEventListener("submit", function(e) {
    e.preventDefault();

    let formData = new FormData(this);

    fetch("/itms.arukustech.com/public/api/inventory_bulk_upload.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {

        let html = `
            <b>Total Rows:</b> ${data.total}<br>
            <b style="color:green">Inserted:</b> ${data.inserted}<br>
            <b style="color:orange">Duplicates:</b> ${data.duplicate}<br>
            <b style="color:blue">Skipped:</b> ${data.skipped}<br><br>
        `;

        if (data.errors.length > 0) {
            html += `<b style="color:red">Errors:</b><br>`;
            html += data.errors.join("<br>");
        }

        Swal.fire({
            title: "Bulk Upload Result",
            html: html,
            icon: data.success ? "success" : "error",
            width: 650,
        });

    })
    .catch(err => {
        Swal.fire("Error", err.message, "error");
    });

});
</script>

<?php include __DIR__ . '/layout_bottom_inventory.php'; ?>
