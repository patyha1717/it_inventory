<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers/auth.php';

check_login();
$pdo = db();

$id = $_GET['id'] ?? 0;
if (!$id) die("Invalid ID");

/* FETCH ITEM */
$stmt = $pdo->prepare("SELECT * FROM rented_inventory WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) die("Record not found");

/* FETCH CATEGORIES */
$categories = $pdo->query("SELECT id, name FROM rented_categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

/* FETCH PROJECTS */
$projects = $pdo->query("SELECT id, project_name FROM rented_projects ORDER BY project_name ASC")->fetchAll(PDO::FETCH_ASSOC);

/* UPDATE */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $invoicePath = $item['invoice_file'];

    // Handle Invoice Replacement
    if (!empty($_FILES['invoice_file']['name'])) {
        // Physical path: public is the root
        $targetDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/rented_invoices/';
        
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        
        $filename = time() . '_' . basename($_FILES['invoice_file']['name']);
        $targetFile = $targetDir . $filename;

        if (move_uploaded_file($_FILES['invoice_file']['tmp_name'], $targetFile)) {
            // Store browser-accessible path in DB
            $invoicePath = '/uploads/rented_invoices/' . $filename;
        }
    }

    $pdo->prepare("
        UPDATE rented_inventory SET
            category_id = ?,
            project_id = ?,
            product_name = ?,
            serial_no = ?,
            quantity = ?,
            purchase_date = ?,
            vendor_name = ?,
            company_name = ?,
            location = ?,
            invoice_file = ?
        WHERE id = ?
    ")->execute([
        $_POST['category_id'],
        $_POST['project_id'], // Added Project ID to update
        $_POST['product_name'],
        $_POST['serial_no'],
        $_POST['quantity'],
        $_POST['purchase_date'],
        $_POST['vendor_name'],
        $_POST['company_name'],
        $_POST['location'],
        $invoicePath,
        $id
    ]);

    header("Location: /rented_inventory_list.php?updated=1");
    exit;
}

include __DIR__ . '/header.php';
?>

<div class="main-content-inner px-4 py-3">
    <h3>Edit Rented Inventory</h3>

    <form method="post" enctype="multipart/form-data" class="card p-3">

        <div class="row">
            <div class="col-md-3">
                <label class="fw-bold">Category</label>
                <select name="category_id" class="form-control" required>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $item['category_id'] == $c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="fw-bold">Project Name</label>
                <select name="project_id" class="form-control" required>
                    <option value="">-- Select Project --</option>
                    <?php foreach ($projects as $pj): ?>
                        <option value="<?= $pj['id'] ?>" <?= $item['project_id'] == $pj['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pj['project_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="fw-bold">Product</label>
                <input name="product_name" class="form-control" value="<?= htmlspecialchars($item['product_name']) ?>" required>
            </div>

            <div class="col-md-3">
                <label class="fw-bold">Serial</label>
                <input name="serial_no" class="form-control" value="<?= htmlspecialchars($item['serial_no']) ?>">
            </div>
        </div>

        <div class="row mt-2">
            <div class="col-md-2">
                <label class="fw-bold">Qty</label>
                <input type="number" name="quantity" class="form-control" value="<?= $item['quantity'] ?>">
            </div>

            <div class="col-md-3">
                <label class="fw-bold">Date</label>
                <input type="date" name="purchase_date" class="form-control" value="<?= $item['purchase_date'] ?>">
            </div>

            <div class="col-md-3">
                <label class="fw-bold">Vendor</label>
                <input name="vendor_name" class="form-control" value="<?= htmlspecialchars($item['vendor_name']) ?>">
            </div>

            <div class="col-md-4">
                <label class="fw-bold">Company</label>
                <input name="company_name" class="form-control" value="<?= htmlspecialchars($item['company_name']) ?>">
            </div>
        </div>

        <div class="row mt-2">
            <div class="col-md-8">
                <label class="fw-bold">Location</label>
                <input name="location" class="form-control" value="<?= htmlspecialchars($item['location']) ?>">
            </div>
            <div class="col-md-4">
                <label class="fw-bold">Replace Invoice</label>
                <input type="file" name="invoice_file" class="form-control">
                <?php if ($item['invoice_file']): ?>
                    <small class="text-success">Current file exists: <a href="<?= $item['invoice_file'] ?>" target="_blank" class="text-decoration-none"><i class="fa fa-file-pdf"></i> View Invoice</a></small>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-4">
            <button class="btn btn-primary">Update Changes</button>
            <a href="/rented_inventory_list.php" class="btn btn-secondary">Cancel</a>
        </div>

    </form>
</div>

<?php include __DIR__ . '/footer.php'; ?>