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
$admin_id = $_SESSION['user_id']; // Track who is processing the return

/* GET ASSIGNMENT DETAILS */
$stmt = $pdo->prepare("
    SELECT rented_inventory_id 
    FROM rented_assignments 
    WHERE id = ? AND status='assigned'
");
$stmt->execute([$assignId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    header("Location: /rented_assign_list.php?error=already_returned");
    exit;
}

try {
    $pdo->beginTransaction();

    /* 1. UPDATE ASSIGNMENT - Sets 'Returned' status and timestamp */
    $pdo->prepare("
        UPDATE rented_assignments 
        SET status='returned',
            returned_at=NOW(),
            returned_by=? 
        WHERE id = ?
    ")->execute([$admin_id, $assignId]);

    /* 2. UPDATE INVENTORY - Makes it available for next assignment */
    $pdo->prepare("
        UPDATE rented_inventory 
        SET status='available',
            assigned_to=NULL,
            assigned_at=NULL,
            project_name=NULL
        WHERE id = ?
    ")->execute([$row['rented_inventory_id']]);

    $pdo->commit();
    header("Location: /rented_assign_list.php?msg=Item returned successfully");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    header("Location: /rented_assign_list.php?error=failed");
    exit;
}