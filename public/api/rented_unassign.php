<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
auth();

if (!isset($_GET['id'])) {
    header("Location: /rented_assign_list.php?error=invalid");
    exit;
}

$pdo = db();
$assignId = (int) $_GET['id'];
$adminId = $_SESSION['user_id'];

// 1. GET DETAILS BEFORE DELETING (Important for the log description)
$stmt = $pdo->prepare("
    SELECT ra.rented_inventory_id, ri.product_name, ri.serial_no 
    FROM rented_assignments ra
    JOIN rented_inventory ri ON ra.rented_inventory_id = ri.id
    WHERE ra.id = ?
");
$stmt->execute([$assignId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    header("Location: /rented_assign_list.php?error=not_found");
    exit;
}

try {
    $pdo->beginTransaction();

    // 2. DELETE THE ASSIGNMENT
    $pdo->prepare("DELETE FROM rented_assignments WHERE id = ?")->execute([$assignId]);

    // 3. RESET INVENTORY STATUS
    $pdo->prepare("
        UPDATE rented_inventory 
        SET status = 'available', assigned_to = NULL, state = NULL, city = NULL, 
            project_name = NULL, remarks = NULL, assigned_at = NULL
        WHERE id = ?
    ")->execute([$row['rented_inventory_id']]);

    // 4. INSERT INTO RENTED_AUDIT_LOG
    $description = "Mistake Correction: Unassigned " . $row['product_name'] . " (SN: " . $row['serial_no'] . ")";
    $pdo->prepare("
        INSERT INTO rented_audit_log (user_id, asset_id, action, description, created_at) 
        VALUES (?, ?, 'unassign', ?, NOW())
    ")->execute([$adminId, $row['rented_inventory_id'], $description]);

    $pdo->commit();
    header("Location: /rented_assign_list.php?msg=Item restored to inventory and logged");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    header("Location: /rented_assign_list.php?error=fail");
    exit;
}