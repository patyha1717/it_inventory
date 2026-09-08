<?php
// header("Content-Type: application/json");

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/helpers/auth.php';

check_login();
$pdo = db();

$id = intval($_POST["id"]);
$category_id = intval($_POST["category_id"]);
$serial_no = $_POST['serial_no'] ?? null;

// Fetch existing item (including invoice file)
$old = $pdo->prepare("SELECT category_id, invoice_file FROM inventory_items WHERE id=?");
$old->execute([$id]);
$old = $old->fetch(PDO::FETCH_ASSOC);

if (!$old) {
    die("Invalid item");
}

/* ----------------------------------------------------
   CATEGORY CHANGE? ? REGENERATE ASSET CODE
-----------------------------------------------------*/
$asset_code = null;

if ($old['category_id'] != $category_id) {

    $cat = $pdo->prepare("SELECT asset_prefix, next_number FROM inv_categories WHERE id=?");
    $cat->execute([$category_id]);
    $cat = $cat->fetch(PDO::FETCH_ASSOC);

    if (!$cat) {
        die("Invalid category");
    }

    $next = str_pad($cat["next_number"], 4, "0", STR_PAD_LEFT);
    $asset_code = $cat["asset_prefix"] . $next;

    // Update next number
    $pdo->prepare("UPDATE inv_categories SET next_number = next_number + 1 WHERE id=?")
        ->execute([$category_id]);
}

/*========Check unique serial_no==========*/
try {
	if (!empty($serial_no)) {

		$stmt = $pdo->prepare("
			SELECT id
			FROM inventory_items
			WHERE serial_no = ?
			  AND id != ?
			LIMIT 1
		");

		$stmt->execute([$serial_no, $id]);

		if ($stmt->fetch(PDO::FETCH_ASSOC)) {
			throw new Exception("Duplicate Serial No!");
		}
	}
} catch (Exception $e) {

   if ($pdo->inTransaction()) {
       $pdo->rollBack();
  }

   ?>
    <script>
        alert(<?= json_encode($e->getMessage()) ?>);
        window.location.href = <?= json_encode("/inventory_edit.php?id=" . $id) ?>;
    </script>
    <?php
    exit;
}

/* ----------------------------------------------------
   UPDATE BASIC FIELDS
-----------------------------------------------------*/
$sql = "
UPDATE inventory_items SET
    category_id = ?,
    item_details = ?,
    brand = ?,
    model = ?,
    serial_no = ?,
    purchase_date = ?,
    invoice_no = ?,
    warranty_date = ?, 
    cost = ?,
    status = ?  -- ADDED THIS LINE
    " . ($asset_code ? ", asset_code = '$asset_code' " : "") . "
WHERE id = ?
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $category_id,
    $_POST['item_details'],
    $_POST['brand'],
    $_POST['model'],
    $_POST['serial_no'],
    $_POST['purchase_date'],
    $_POST['invoice_no'],
    $_POST['warranty_date'],
    $_POST['cost'],
    $_POST['status'], // ADDED THIS LINE
    $id
]);


/* ----------------------------------------------------
   INVOICE FILE UPLOAD (OPTIONAL!)
-----------------------------------------------------*/
if (!empty($_FILES["invoice_file"]["name"])) {

    // folder inside public
    $uploadDir = __DIR__ . "/../../public/uploads/invoices/" . $id . "/";

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // remove old file if exists
    if (!empty($old['invoice_file'])) {
        $oldFile = __DIR__ . "/../../public" . $old['invoice_file'];
        if (file_exists($oldFile)) {
            unlink($oldFile);
        }
    }

    // upload new invoice
    $ext = pathinfo($_FILES["invoice_file"]["name"], PATHINFO_EXTENSION);
    $fileName = "invoice_" . time() . "." . $ext;

    $fullPath = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES["invoice_file"]["tmp_name"], $fullPath)) {

        // save path accessible from browser
        $invoice_path = "/uploads/invoices/$id/$fileName";

        $upd = $pdo->prepare("UPDATE inventory_items SET invoice_file=? WHERE id=?");
        $upd->execute([$invoice_path, $id]);
    }
}

/* ----------------------------------------------------
   REDIRECT BACK
-----------------------------------------------------*/
header("Location: /inventory_list.php?updated=1");
exit;
?>
