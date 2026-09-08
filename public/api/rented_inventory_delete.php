<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/helpers/auth.php';

// Only allow logged-in users with proper roles to delete
check_login();

// Optional: If you want to restrict delete to only Superadmins
// if (!is_superadmin()) { die("Access denied"); }

$pdo = db();

$id = $_GET['id'] ?? 0;
if (!$id) {
    die("Invalid request");
}

/* 1. FETCH RECORD DETAILS (For file deletion and logging) */
$stmt = $pdo->prepare("SELECT product_name, invoice_file FROM rented_inventory WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    die("Record not found");
}

/* 2. DELETE INVOICE FILE FROM SERVER */
if (!empty($item['invoice_file'])) {
    // $_SERVER['DOCUMENT_ROOT'] ensures we find the absolute path to the 'public' folder
    $physicalPath = $_SERVER['DOCUMENT_ROOT'] . $item['invoice_file'];
    
    if (file_exists($physicalPath)) {
        unlink($physicalPath);
    }
}

/* 3. DELETE RECORD FROM DATABASE */
$delete = $pdo->prepare("DELETE FROM rented_inventory WHERE id = ?");
$result = $delete->execute([$id]);

/* 4. OPTIONAL: LOG ACTIVITY */
// If you have a logActivity function, it's good to track who deleted what
// logActivity("Rented Item Deleted", "Deleted item: " . $item['product_name'] . " (ID: $id)");

if ($result) {
    header("Location: /rented_inventory_list.php?deleted=1");
} else {
    header("Location: /rented_inventory_list.php?error=delete_failed");
}
exit;