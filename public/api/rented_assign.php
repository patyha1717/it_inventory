<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
auth();

// UPDATED: Using specialized rented handover utilities
require_once __DIR__ . '/../handovers/rented_generate_handover_pdf.php';
require_once __DIR__ . '/../handovers/rented_send_handover_mail.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /rented_assign.php?error=invalid");
    exit;
}

$pdo = db();

// Capture inputs
$rented_inventory_id = $_POST['rented_inventory_id'] ?? null;
$user_id            = $_POST['user_id'] ?? null;
$state              = $_POST['state'] ?? null;
$city               = $_POST['city'] ?? null;
$project_name       = $_POST['project_name'] ?? null;
$remarks            = $_POST['remarks'] ?? null;
$assigned_by        = $_SESSION['user_id'];

// Basic Validation
if (!$rented_inventory_id || !$user_id) {
    header("Location: /rented_assign.php?error=missing");
    exit;
}

try {
    $pdo->beginTransaction();

    /* =======================================================
        1. CHECK IF ITEM IS STILL AVAILABLE
    ======================================================= */
    $stCheck = $pdo->prepare("SELECT status, product_name, serial_no FROM rented_inventory WHERE id = ? FOR UPDATE");
    $stCheck->execute([$rented_inventory_id]);
    $item_data = $stCheck->fetch(PDO::FETCH_ASSOC);

    if (!$item_data || $item_data['status'] !== 'available') {
        throw new Exception("not_available");
    }

    /* =======================================================
        2. INSERT INTO ASSIGNMENTS TABLE
    ======================================================= */
    $st = $pdo->prepare("
        INSERT INTO rented_assignments
        (rented_inventory_id, user_id, state, city, project_name, remarks, assigned_by, assigned_at, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'assigned')
    ");
    $st->execute([
        $rented_inventory_id,
        $user_id,
        $state,
        $city,
        $project_name,
        $remarks,
        $assigned_by
    ]);

    /* =======================================================
        3. UPDATE INVENTORY STATUS
    ======================================================= */
    $st2 = $pdo->prepare("
        UPDATE rented_inventory
        SET status = 'assigned',
            assigned_to = ?,
            state = ?,
            city = ?,
            project_name = ?,
            remarks = ?,
            assigned_at = NOW()
        WHERE id = ?
    ");
    $st2->execute([
        $user_id,
        $state,
        $city,
        $project_name,
        $remarks,
        $rented_inventory_id
    ]);

    /* =======================================================
        NEW: 4. RECORD IN RENTED_AUDIT_LOG
    ======================================================= */
    // Fetch assigned user's name for a descriptive log
    $stUser = $pdo->prepare("SELECT name FROM users WHERE id = ?");
    $stUser->execute([$user_id]);
    $target_user_name = $stUser->fetchColumn();

    $log_desc = "Assigned: " . $item_data['product_name'] . " (SN: " . $item_data['serial_no'] . ") to " . $target_user_name . " at Project: " . $project_name;
    
    $stLog = $pdo->prepare("
        INSERT INTO rented_audit_log (user_id, asset_id, action, description, created_at) 
        VALUES (?, ?, 'assign', ?, NOW())
    ");
    $stLog->execute([$assigned_by, $rented_inventory_id, $log_desc]);

    $pdo->commit();

    /* =======================================================
        5. GENERATE RENTED PDF & SEND EMAIL
    ======================================================= */
    
    // Fetch employee details
    $stmt = $pdo->prepare("SELECT name, email, employee_id, department FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $emp = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($emp && $item_data) {
        $employee = [
            'name'          => $emp['name'],
            'code'          => $emp['employee_id'] ?? 'N/A',
            'department'    => $emp['department'] ?? 'N/A',
            'location'      => "{$city}, {$state}",
            'handover_date' => date('Y-m-d'),
            'handover_by'   => $_SESSION['name'] ?? 'Admin'
        ];

        $assets = [[
            'item_details'    => $item_data['product_name'],
            'project_name' => $project_name, 
            'serial_no'    => $item_data['serial_no'],
            'remarks'      => $remarks
        ]];

        $pdf = generateRentedHandoverPDF($employee, $assets, "Good", $remarks);
        sendRentedHandoverMail($emp['email'], $pdf); 
    }

    header("Location: /rented_assign.php?success=1");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $err_type = ($e->getMessage() == "not_available") ? "not_available" : "failed";
    header("Location: /rented_assign.php?error=" . $err_type);
    exit;
}