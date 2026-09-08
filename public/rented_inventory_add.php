<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

auth();
$pdo = db();
$msg = '';

/* Helper function for file uploads updated for /public root */
function uploadRentedInvoice($file) {
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) return null;
    
    // Physical path on server (public is the root)
    $targetDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/rented_invoices/';
    
    // Create directory if it doesn't exist
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
    
    $filename = time() . '_' . basename($file['name']);
    $targetFile = $targetDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        // Return browser-accessible path (relative to public root)
        return '/uploads/rented_invoices/' . $filename;
    }
    return null;
}

/* FETCH CATEGORIES */
$categories = $pdo->query("
    SELECT id, name 
    FROM rented_categories 
    WHERE status = 1 
    ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);

/* FETCH RENTED PROJECTS */
$projects = $pdo->query("
    SELECT id, project_name 
    FROM rented_projects 
    ORDER BY project_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

/* SINGLE ADD */
if (isset($_POST['single_submit'])) {

    $invoicePath = uploadRentedInvoice($_FILES['invoice_file'] ?? null);

    $stmt = $pdo->prepare("
        INSERT INTO rented_inventory
        (category_id, project_id, product_name, serial_no, quantity, purchase_date, vendor_name, company_name, location, invoice_file, created_by, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $stmt->execute([
        $_POST['category_id'],
        $_POST['project_id'], // Added Project ID
        $_POST['product_name'],
        $_POST['serial_no'],
        $_POST['quantity'],
        $_POST['purchase_date'],
        $_POST['vendor_name'],
        $_POST['company_name'],
        $_POST['location'],
        $invoicePath,
        $_SESSION['user_id']
    ]);

    header("Location: rented_inventory_list.php?success=1");
    exit;
}

/* BULK UPLOAD (.XLSX) */
if (isset($_POST['bulk_submit']) && !empty($_FILES['excel']['tmp_name'])) {

    // Single invoice to be assigned to all items in this Excel
    $invoicePath = uploadRentedInvoice($_FILES['bulk_invoice'] ?? null);

    $spreadsheet = IOFactory::load($_FILES['excel']['tmp_name']);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();

    unset($rows[0]); // remove header row

    foreach ($rows as $row) {
        if (empty($row[0]) || empty($row[1])) continue;

        // Find Category ID
        $cat = $pdo->prepare("SELECT id FROM rented_categories WHERE name = ?");
        $cat->execute([trim($row[0])]);
        $category_id = $cat->fetchColumn();

        // Find Project ID (Column 8 in Excel - index 7)
        $proj = $pdo->prepare("SELECT id FROM rented_projects WHERE project_name = ?");
        $proj->execute([trim($row[8])]);
        $project_id = $proj->fetchColumn();

        if (!$category_id) continue;

        $pdo->prepare("
            INSERT INTO rented_inventory
            (category_id, project_id, product_name, serial_no, quantity, purchase_date, vendor_name, company_name, location, invoice_file, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ")->execute([
            $category_id,
            $project_id ? $project_id : null, // Save Project ID
            trim($row[1]),
            trim($row[2]),
            (int)$row[3],
            $row[4],
            trim($row[5]),
            trim($row[6]),
            trim($row[7]),
            $invoicePath,
            $_SESSION['user_id']
        ]);
    }

    header("Location: rented_inventory_list.php?success=1");
    exit;
}

include __DIR__ . '/header.php';
?>

<div class="main-content-inner px-4 py-3 w-100">
<div class="container-fluid">

<h3 class="mb-3">Rented Inventory Add</h3>

<ul class="nav nav-tabs mb-3">
  <li class="nav-item">
    <a class="nav-link active" data-bs-toggle="tab" href="#single">Single Add</a>
  </li>
  <li class="nav-item">
    <a class="nav-link" data-bs-toggle="tab" href="#bulk">Bulk Upload</a>
  </li>
</ul>

<div class="tab-content">

<div class="tab-pane fade show active" id="single">
<form method="post" enctype="multipart/form-data" class="card p-3">

<div class="row">
<div class="col-md-3">
<label>Category *</label>
<select name="category_id" class="form-control" required>
<option value="">-- Select --</option>
<?php foreach ($categories as $c): ?>
<option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
<?php endforeach; ?>
</select>
</div>

<div class="col-md-3">
<label>Project Name *</label>
<select name="project_id" class="form-control" required>
<option value="">-- Select Project --</option>
<?php foreach ($projects as $p): ?>
<option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['project_name']) ?></option>
<?php endforeach; ?>
</select>
</div>

<div class="col-md-3">
<label>Product Name *</label>
<input name="product_name" class="form-control" required>
</div>

<div class="col-md-3">
<label>Serial No *</label>
<input name="serial_no" class="form-control" required>
</div>
</div>

<div class="row mt-2">
<div class="col-md-2">
<label>Quantity *</label>
<input type="number" name="quantity" class="form-control" required>
</div>

<div class="col-md-3">
<label>Date *</label>
<input type="date" name="purchase_date" class="form-control" required>
</div>

<div class="col-md-3">
<label>Vendor</label>
<input name="vendor_name" class="form-control">
</div>

<div class="col-md-4">
<label>Company</label>
<input name="company_name" class="form-control">
</div>
</div>

<div class="row mt-2">
<div class="col-md-8">
<label>Location</label>
<input name="location" class="form-control">
</div>
<div class="col-md-4">
<label>Upload Invoice</label>
<input type="file" name="invoice_file" class="form-control">
</div>
</div>

<button name="single_submit" class="btn btn-primary mt-3">Save</button>
</form>
</div>

<div class="tab-pane fade" id="bulk">
<form method="post" enctype="multipart/form-data" class="card p-3">

<div class="row">
<div class="col-md-6">
<label>Upload Excel (.xlsx) *</label>
<input type="file" name="excel" accept=".xlsx" class="form-control" required>
</div>
<div class="col-md-6">
<label>Upload Invoice for all Items (Optional)</label>
<input type="file" name="bulk_invoice" class="form-control">
</div>
</div>

<a href="/assets/samples/rented_inventory_sample.xlsx" class="btn btn-link mt-2">
Download Sample Excel
</a>

<small class="text-muted d-block mt-2">
Columns: category | product_name | serial_no | quantity | date | vendor | company | location | <strong>project_name</strong>
</small>

<button name="bulk_submit" class="btn btn-success mt-3 w-100">Upload</button>
</form>
</div>

</div>
</div>
</div>

<?php include __DIR__ . '/footer.php'; ?>