<?php
// DEBUG
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
require_once __DIR__ . '/../../app/helpers/activity_logger.php';

check_login();
$pdo = db();

require __DIR__ . '/../../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

/* ------------------------------------------------
   Validate Excel File
-------------------------------------------------*/
if (!isset($_FILES["excel_file"]) || $_FILES["excel_file"]["error"] !== 0) {
    echo json_encode([
        "success" => false,
        "message" => "Excel file is required"
    ]);
    exit;
}

$filePath = $_FILES["excel_file"]["tmp_name"];

/* ------------------------------------------------
   Read Excel
-------------------------------------------------*/
try {
    $spreadsheet = IOFactory::load($filePath);
    $sheet       = $spreadsheet->getActiveSheet();
    $rows        = $sheet->toArray(null, true, true, true);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid Excel file: " . $e->getMessage()
    ]);
    exit;
}

/* ------------------------------------------------
   Handle OPTIONAL Common Invoice Upload
-------------------------------------------------*/
$invoice_path = null;

if (!empty($_FILES["invoice_file"]["name"])) {

    $batch = "batch_" . time();
    $uploadDir = __DIR__ . "/../../public/uploads/invoices/$batch/";

    if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);

    $ext = strtolower(pathinfo($_FILES["invoice_file"]["name"], PATHINFO_EXTENSION));
    $fileName = "invoice_" . time() . "." . $ext;

    if (move_uploaded_file($_FILES["invoice_file"]["tmp_name"], $uploadDir . $fileName)) {
        $invoice_path = "/uploads/invoices/$batch/$fileName";
    }
}

/* ------------------------------------------------
   Counters
-------------------------------------------------*/
$totalRows = 0;
$inserted  = 0;
$duplicate = 0;
$skipped   = 0;
$errors    = [];

$insertedItemIDs = [];

/* ------------------------------------------------
   START TRANSACTION
-------------------------------------------------*/
try {
    $pdo->beginTransaction();

    foreach ($rows as $i => $row) {

        if ($i == 1) continue; // header row
        $totalRows++;

        $category_name = trim($row["A"]);
        $item_details     = trim($row["B"]);
        $brand         = trim($row["C"]);
        $model         = trim($row["D"]);
        $serial_no     = trim($row["E"]);
        $purchase_date = $row["F"];
        $invoice_no    = trim($row["G"]);
        $warranty_end  = $row["H"];
        $cost          = trim($row["I"]);

        if ($category_name === "" || $item_details === "") {
            $skipped++;
            $errors[] = "Row $i: Missing category or item name";
            continue;
        }

        /* Convert Excel date values */
        if (is_numeric($purchase_date)) {
            $purchase_date = Date::excelToDateTimeObject($purchase_date)->format('Y-m-d');
        }
        if (is_numeric($warranty_end)) {
            $warranty_end = Date::excelToDateTimeObject($warranty_end)->format('Y-m-d');
        }

        /* Fetch category WITH LOCK */
        $stmt = $pdo->prepare("
            SELECT id, asset_prefix, next_number 
            FROM inv_categories 
            WHERE name = ?
            FOR UPDATE
        ");
        $stmt->execute([$category_name]);
        $cat = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cat) {
            $skipped++;
            $errors[] = "Row $i: Category '$category_name' not found";
            continue;
        }

        $category_id = $cat["id"];
        $asset_code  = $cat["asset_prefix"]
                     . str_pad($cat["next_number"], 4, "0", STR_PAD_LEFT);

        /* Duplicate Serial Check */
        if ($serial_no !== "") {
            $chk = $pdo->prepare("SELECT id FROM inventory_items WHERE serial_no = ?");
            $chk->execute([$serial_no]);
            if ($chk->fetch()) {
                $duplicate++;
                $errors[] = "Row $i: Duplicate serial '$serial_no'";
                continue;
            }
        }

        /* Insert Inventory Item */
        $pdo->prepare("
            INSERT INTO inventory_items
            (
                category_id, item_details, brand, model,
                serial_no, purchase_date, invoice_no,
                warranty_date, cost, asset_code, status
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'available')
        ")->execute([
            $category_id,
            $item_details,
            $brand,
            $model,
            $serial_no === "" ? null : $serial_no,
            $purchase_date ?: null,
            $invoice_no,
            $warranty_end ?: null,
            $cost,
            $asset_code
        ]);

        $item_id = $pdo->lastInsertId();
        $insertedItemIDs[] = $item_id;

        /* Update category counter */
        $pdo->prepare("
            UPDATE inv_categories 
            SET next_number = next_number + 1 
            WHERE id = ?
        ")->execute([$category_id]);

        $inserted++;
    }

    /* Assign common invoice */
    if ($invoice_path && !empty($insertedItemIDs)) {
        $update = $pdo->prepare("UPDATE inventory_items SET invoice_file = ? WHERE id = ?");
        foreach ($insertedItemIDs as $id) {
            $update->execute([$invoice_path, $id]);
        }
    }

    /* ACTIVITY LOG */
    logActivity(
        "Bulk Inventory Upload",
        "Total: $totalRows, Inserted: $inserted, Duplicate: $duplicate"
    );

    $pdo->commit();

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        "success" => false,
        "message" => "Upload failed",
        "error"   => $e->getMessage()
    ]);
    exit;
}

/* ------------------------------------------------
   FINAL RESPONSE
-------------------------------------------------*/
echo json_encode([
    "success"   => true,
    "total"     => $totalRows,
    "inserted"  => $inserted,
    "duplicate" => $duplicate,
    "skipped"   => $skipped,
    "errors"    => $errors
]);
exit;
