<?php
// DEBUG ENABLED
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
require_once __DIR__ . '/../../app/helpers/activity_logger.php';

auth();

require_once __DIR__ . '/../../config/env_loader.php';
require_once __DIR__ . '/../handovers/generate_handover_pdf.php';
require_once __DIR__ . '/../handovers/generate_handover_docx.php';
require_once __DIR__ . '/../handovers/send_handover_mail.php';

if (!in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header("Location: /assign_items.php?error=denied");
    exit;
}

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /assign_items.php?error=invalid");
    exit;
}

/* -------------------------------
   INPUT
--------------------------------*/
$item_id = intval($_POST['item_id'] ?? 0);
$user_id = intval($_POST['user_id'] ?? 0);
$remarks = trim($_POST['remarks'] ?? '');

/* LOCATION */
$state = trim($_POST['state'] ?? '');
$city  = trim($_POST['city'] ?? '');

if ($item_id <= 0 || $user_id <= 0) {
    header("Location: /assign_items.php?error=missing");
    exit;
}

if ($state === '' || $city === '') {
    $state = 'N/A';
    $city  = 'N/A';
}

try {

    $pdo->beginTransaction();

    /* ----------------------------------------------------
       FETCH ITEM + CATEGORY (LOCKED)
    -----------------------------------------------------*/
    $stmt = $pdo->prepare("
        SELECT 
            i.*, 
            c.asset_prefix, 
            c.next_number,
            c.id AS cat_id
        FROM inventory_items i
        JOIN inv_categories c ON c.id = i.category_id
        WHERE i.id = ?
        FOR UPDATE
    ");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        throw new Exception("Item not found");
    }

    if ($item['status'] !== 'available') {
        throw new Exception("Item not available");
    }

    /* BUILD ASSET CODE - Updated to pick from inventory_items table column */
    $asset_code = $item['asset_code'];

    /* ----------------------------------------------------
       FETCH USER
    -----------------------------------------------------*/
    $stmt = $pdo->prepare("
        SELECT name, email, employee_id, department 
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$user_id]);
    $emp = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$emp) {
        throw new Exception("User not found");
    }

    /* ----------------------------------------------------
       INSERT ASSIGNMENT (WITH LOCATION)
    -----------------------------------------------------*/
    $pdo->prepare("
        INSERT INTO inventory_assignments
        (
            item_id, user_id, assigned_by,
            remarks, state, city, assigned_on
        )
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ")->execute([
        $item_id,
        $user_id,
        $_SESSION['user_id'],
        $remarks,
        $state,
        $city
    ]);

    /* UPDATE ITEM STATUS */
    $pdo->prepare("
        UPDATE inventory_items
        SET status = 'assigned'
        WHERE id = ?
    ")->execute([$item_id]);

    /* UPDATE CATEGORY COUNTER */
    $pdo->prepare("
        UPDATE inv_categories
        SET next_number = next_number + 1
        WHERE id = ?
    ")->execute([$item['cat_id']]);

    /* ----------------------------------------------------
       AUDIT LOG (LOCATION IN DESCRIPTION)
    -----------------------------------------------------*/
    $audit_msg = "{$item['item_details']} ({$asset_code}) assigned to {$emp['name']} at {$city}, {$state}";

    $pdo->prepare("
        INSERT INTO audit_log
        (user_id, asset_id, action, description, created_at)
        VALUES (?, ?, 'assign', ?, NOW())
    ")->execute([
        $user_id,
        $item_id,
        $audit_msg
    ]);

    $pdo->commit();
   
/* USER ACTIVITY LOG */
logActivity(
    "Item Assigned",
    "Item: {$item['item_details']} ({$asset_code}) assigned to {$emp['name']} at {$city}, {$state}"
);


} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    header("Location: /assign_items.php?error=failed");
    exit;
}

/* ----------------------------------------------------
   PDF & DOCX GENERATION
-----------------------------------------------------*/
$employee = [
    'name'          => $emp['name'],
    'code'          => $emp['employee_id'],
    'department'    => $emp['department'],
    'location'      => "{$city}, {$state}",
    'handover_date' => date('Y-m-d'),
    'handover_by'   => $_SESSION['name'] ?? 'Admin'
];

$assets = [
    [
        'item_details'  => $item['item_details'],
        'asset_code' => $asset_code,
        'serial_no'  => $item['serial_no'],
        'remarks'    => $remarks
    ]
];

/* Generate Documents */
$pdf = generateHandoverPDF($employee, $assets, "Good", $remarks);
/* $doc = generateHandoverDOCX($employee, $assets, "Good", $remarks); */

/* Send Email */
sendHandoverMail($emp['email'], $pdf, $doc);

/* Redirect */
header("Location: /assign_items.php?success=1");
exit;
