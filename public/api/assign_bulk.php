<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
require_once __DIR__ . '/../../app/helpers/activity_logger.php';
auth();

require_once __DIR__ . '/../../config/env_loader.php';
require_once __DIR__ . '/../handovers/generate_handoverbulk_pdf.php';
require_once __DIR__ . '/../handovers/generate_handover_docx.php';
require_once __DIR__ . '/../handovers/send_handover_mail.php';

if (!in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header("Location: /multi_assign.php?error=denied");
    exit;
}

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /multi_assign.php?error=invalid");
    exit;
}

/* -------------------------------
   INPUT
--------------------------------*/
$user_id  = intval($_POST['user_id']);
$item_ids = $_POST['item_ids'] ?? [];
$remarks  = trim($_POST['remarks'] ?? '');

/* ? NEW: LOCATION */
$state = trim($_POST['state'] ?? '');
$city  = trim($_POST['city'] ?? '');

if ($state === '' || $city === '') {
    $state = 'N/A';
    $city  = 'N/A';
}

if ($user_id <= 0 || empty($item_ids)) {
    header("Location: /multi_assign.php?error=missing");
    exit;
}

/* ---------------------------------------
   FETCH EMPLOYEE NAME (VERY IMPORTANT)
-----------------------------------------*/
$stmt = $pdo->prepare("
    SELECT name, email, employee_id, department 
    FROM users 
    WHERE id = ?
");
$stmt->execute([$user_id]);
$emp = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$emp) {
    header("Location: /multi_assign.php?error=invaliduser");
    exit;
}

$employee_name = $emp['name'];

try {
    $pdo->beginTransaction();

    $assets = [];

    foreach ($item_ids as $item_id) {

        /* FETCH ITEM WITH CATEGORY */
        $stmt = $pdo->prepare("
            SELECT i.*, 
                   c.asset_prefix,
                   c.next_number,
                   c.id AS cat_id
            FROM inventory_items i
            LEFT JOIN inv_categories c ON c.id = i.category_id
            WHERE i.id = ?
        ");
        $stmt->execute([$item_id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item || $item['status'] !== 'available') {
            throw new Exception("Item unavailable");
        }

        /* CREATE ASSET CODE */
        $asset_code = $item['asset_code'];

        /* UPDATE CATEGORY NEXT NUMBER */
        $pdo->prepare("
            UPDATE inv_categories 
            SET next_number = next_number + 1 
            WHERE id = ?
        ")->execute([$item['cat_id']]);

        /* ---------------------------------------
           INSERT ASSIGNMENT (STATE & CITY)
        ----------------------------------------*/
        $pdo->prepare("
            INSERT INTO inventory_assignments 
            (item_id, user_id, assigned_by, remarks, state, city, assigned_on)
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
            SET status='assigned' 
            WHERE id = ?
        ")->execute([$item_id]);

        /* ------------------------------------
           AUDIT LOG (WITH LOCATION)
        -------------------------------------*/
        $audit_msg = "{$item['item_details']} ({$asset_code}) assigned to {$employee_name} at {$city}, {$state}";

        $pdo->prepare("
            INSERT INTO audit_log 
            (user_id, asset_id, action, description, created_at)
            VALUES (?, ?, 'assign', ?, NOW())
        ")->execute([
            $user_id,
            $item_id,
            $audit_msg
        ]);

        /* ADD TO PDF LIST */
        $assets[] = [
            'item_details'  => $item['item_details'],
            'asset_code' => $asset_code,
            'serial_no'  => $item['serial_no'],
            'remarks'    => $remarks
        ];
    }

    $pdo->commit();

/* USER ACTIVITY LOG */
logActivity(
    "Bulk Item Assigned",
    "Total Items: " . count($assets) .
    ", Assigned to: {$emp['name']}" .
    ", Location: {$city}, {$state}"
);

} catch (Exception $e) {
    $pdo->rollBack();
    header("Location: /multi_assign.php?error=failed");
    exit;
}

/* ---------------------------------------
   PREPARE DATA FOR PDF / DOCX
-----------------------------------------*/
$employee = [
    'name'          => $emp['name'],
    'code'          => $emp['employee_id'],
    'department'    => $emp['department'],
    'location'      => $city . ', ' . $state,   // ? LOCATION
    'handover_date' => date('Y-m-d'),
    'handover_by'   => $_SESSION['name'] ?? 'Admin'
];

/* GENERATE DOCUMENTS */
$pdf = generateHandoverbulkPDF($employee, $assets, "Good", $remarks);
/* $doc = generateHandoverDOCX($employee, $assets, "Good", $remarks); */

/* SEND EMAIL */
sendHandoverMail($emp['email'], $pdf, $doc);

/* REDIRECT */
header("Location: /multi_assign.php?success=1");
exit;
