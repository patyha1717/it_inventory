<?php
header("Content-Type: application/json");

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
require_once __DIR__ . '/../../app/helpers/activity_logger.php';

auth();
$pdo = db();

/* =============================
   VALIDATE INPUT
============================= */
$category_id   = $_POST["category_id"] ?? null;
$item_details  = trim($_POST["item_details"] ?? '');
$brand         = $_POST["brand"] ?? null;
$model         = $_POST["model"] ?? null;
$serial_no     = $_POST["serial_no"] ?? null;
$purchase_date = $_POST["purchase_date"] ?? null;
$invoice_no    = $_POST["invoice_no"] ?? null;
$warranty_date = $_POST["warranty_date"] ?? null;
$cost          = $_POST["cost"] ?? null;

if (!$category_id || !$item_details) {
    echo json_encode([
        "status" => "error",
        "message" => "Missing required fields"
    ]);
    exit;
}

try {
    /* =============================
       BEGIN TRANSACTION
    ============================= */
    $pdo->beginTransaction();
	
	/*========Check unique serial_no==========*/
	if (!empty($serial_no)) {

		$stmt = $pdo->prepare("
			SELECT serial_no
			FROM inventory_items
			WHERE serial_no = ?
			FOR UPDATE
		");

		$stmt->execute([$serial_no]);

		$item = $stmt->fetch(PDO::FETCH_ASSOC);
		
		if ($item) {
			throw new Exception("Duplicate Serial No");
		}
	}

    /* STEP 1: FETCH CATEGORY */
    $cat = $pdo->prepare("
        SELECT asset_prefix, next_number 
        FROM inv_categories 
        WHERE id = ?
        FOR UPDATE
    ");
    $cat->execute([$category_id]);
    $category = $cat->fetch(PDO::FETCH_ASSOC);

    if (!$category) {
        throw new Exception("Invalid category");
    }

    $asset_code = $category['asset_prefix']
        . str_pad($category['next_number'], 4, "0", STR_PAD_LEFT);

    /* STEP 2: INSERT INVENTORY */
    $stmt = $pdo->prepare("
        INSERT INTO inventory_items
        (
            category_id, item_details, brand, model, serial_no,
            purchase_date, invoice_no, warranty_date,
            cost, asset_code, status
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'available')
    ");

    $stmt->execute([
        $category_id,
        $item_details,
        $brand,
        $model,
        $serial_no,
        $purchase_date,
        $invoice_no,
        $warranty_date,
        $cost,
        $asset_code
    ]);

    $item_id = $pdo->lastInsertId();

    /* STEP 3: OPTIONAL INVOICE UPLOAD */
    $invoice_path = null;

    if (!empty($_FILES['invoice_file']['name'])) {

        $uploadDir = __DIR__ . "/../../public/uploads/invoices/$item_id/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $ext  = strtolower(pathinfo($_FILES['invoice_file']['name'], PATHINFO_EXTENSION));
        $file = "invoice_" . time() . "." . $ext;

        if (!move_uploaded_file($_FILES['invoice_file']['tmp_name'], $uploadDir . $file)) {
            throw new Exception("Invoice upload failed");
        }

        $invoice_path = "/uploads/invoices/$item_id/$file";

        $pdo->prepare("
            UPDATE inventory_items 
            SET invoice_file = ? 
            WHERE id = ?
        ")->execute([$invoice_path, $item_id]);
    }

    /* STEP 4: UPDATE CATEGORY COUNTER */
    $pdo->prepare("
        UPDATE inv_categories 
        SET next_number = next_number + 1 
        WHERE id = ?
    ")->execute([$category_id]);

    /* STEP 5: ACTIVITY LOG */
    logActivity(
        "Inventory Added",
        "Item: {$item_details}, Asset Code: {$asset_code}"
    );

    /* COMMIT */
    $pdo->commit();

    echo json_encode([
        "status"       => "success",
        "asset_code"   => $asset_code,
        "invoice_file" => $invoice_path
    ]);
    exit;

} catch (Exception $e) {

   if ($pdo->inTransaction()) {
       $pdo->rollBack();
  }

   echo json_encode([
       "status"  => "error",
       "message" => $e->getMessage()
   ]);
   exit;
}
