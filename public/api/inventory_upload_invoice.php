<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../../app/db.php';

$pdo = db();

/* Validate item ID */
if (!isset($_POST['item_id'])) {
    echo json_encode(["status" => "error", "message" => "Missing item ID"]);
    exit;
}

$item_id = intval($_POST['item_id']);

/* Validate file */
if (!isset($_FILES['invoice_file']) || $_FILES['invoice_file']['error'] !== 0) {
    echo json_encode(["status" => "error", "message" => "No valid file uploaded"]);
    exit;
}

/* Create MAIN invoice folder (NO ITEM SUBFOLDER) */
$uploadDir = __DIR__ . '/../../public/uploads/invoices/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

/* Generate unique filename */
$ext = strtolower(pathinfo($_FILES['invoice_file']['name'], PATHINFO_EXTENSION));
$filename = "invoice_" . $item_id . "_" . time() . "." . $ext;

$serverPath = $uploadDir . $filename;

/* Move file to server */
if (!move_uploaded_file($_FILES['invoice_file']['tmp_name'], $serverPath)) {
    echo json_encode(["status" => "error", "message" => "File upload failed"]);
    exit;
}

/* PUBLIC URL (Browser-accessible path) */
$publicUrl = "/uploads/invoices/" . $filename;

/* Update DB */
$stmt = $pdo->prepare("UPDATE inventory_items SET invoice_file = ? WHERE id = ?");
$stmt->execute([$publicUrl, $item_id]);

echo json_encode([
    "status"       => "success",
    "message"      => "Invoice uploaded successfully",
    "invoice_file" => $publicUrl
]);
?>
