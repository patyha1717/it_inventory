<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
auth();

// Check if IDs are provided
if (empty($_POST['ids']) || !is_array($_POST['ids'])) {
    header("Location: /rented_assign_list.php?error=No items selected");
    exit;
}

$pdo = db();
$admin_id = $_SESSION['user_id']; // The admin performing the return

try {
    $pdo->beginTransaction();

    // 1. Prepare Statement: Mark assignment as returned
    $stAssign = $pdo->prepare("
        UPDATE rented_assignments
        SET status = 'returned', 
            returned_at = NOW(),
            returned_by = ? 
        WHERE id = ? AND status = 'assigned'
    ");

    // 2. Prepare Statement: Reset inventory status and clear previous user link
    $stInv = $pdo->prepare("
        UPDATE rented_inventory ri
        JOIN rented_assignments ra ON ri.id = ra.rented_inventory_id
        SET ri.status = 'available',
            ri.assigned_to = NULL,
            ri.assigned_at = NULL
        WHERE ra.id = ?
    ");

    foreach ($_POST['ids'] as $id) {
        $stAssign->execute([$admin_id, $id]);
        $stInv->execute([$id]);
    }

    $pdo->commit();
    header("Location: /rented_assign_list.php?msg=Items marked as returned successfully");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // You can change 'die' to a redirect with an error message
    header("Location: /rented_assign_list.php?error=Return process failed");
    exit;
}