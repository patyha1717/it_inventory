<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
auth();

// Using the specialized rented handover utilities
require_once __DIR__ . '/../handovers/rented_generate_handover_pdf.php';
require_once __DIR__ . '/../handovers/rented_send_handover_mail.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /rented_assign.php?error=invalid");
    exit;
}

$pdo = db();

$user_id      = $_POST['user_id'] ?? null;
$item_ids     = $_POST['rented_inventory_ids'] ?? []; 
$state        = $_POST['state'] ?? null;
$city         = $_POST['city'] ?? null;
$project_name = $_POST['project_name'] ?? null;
$remarks      = $_POST['remarks'] ?? null;
$assigned_by  = $_SESSION['user_id']; 

if (!$user_id || empty($item_ids)) {
    header("Location: /rented_assign.php?error=missing");
    exit;
}

try {
    $pdo->beginTransaction();

    // Fetch Target User Name for the Audit Log
    $stUserName = $pdo->prepare("SELECT name FROM users WHERE id = ?");
    $stUserName->execute([$user_id]);
    $target_user_name = $stUserName->fetchColumn();

    $assigned_items_data = []; 

    // Prepare statements
    $stmtInsert = $pdo->prepare("
        INSERT INTO rented_assignments 
        (rented_inventory_id, user_id, state, city, project_name, remarks, assigned_by, assigned_at, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'assigned')
    ");

    $stmtUpdateInv = $pdo->prepare("
        UPDATE rented_inventory 
        SET status = 'assigned', 
            assigned_to = ?, 
            state = ?, 
            city = ?, 
            project_name = ?, 
            remarks = ?, 
            assigned_at = NOW() 
        WHERE id = ? AND status = 'available'
    ");

    $stmtFetchItem = $pdo->prepare("SELECT product_name, serial_no FROM rented_inventory WHERE id = ?");

    // NEW: Prepare Audit Log Statement
    $stmtAudit = $pdo->prepare("
        INSERT INTO rented_audit_log (user_id, asset_id, action, description, created_at) 
        VALUES (?, ?, 'assign', ?, NOW())
    ");

    foreach ($item_ids as $inventory_id) {
        // 1. Update Inventory Status first to check availability
        $stmtUpdateInv->execute([$user_id, $state, $city, $project_name, $remarks, $inventory_id]);

        if ($stmtUpdateInv->rowCount() > 0) {
            // 2. Insert into assignments table
            $stmtInsert->execute([$inventory_id, $user_id, $state, $city, $project_name, $remarks, $assigned_by]);

            // 3. Fetch details for PDF and Audit Log
            $stmtFetchItem->execute([$inventory_id]);
            $item = $stmtFetchItem->fetch(PDO::FETCH_ASSOC);
            
            if ($item) {
                // Add to PDF array
                $assigned_items_data[] = [
                    'item_details'    => $item['product_name'],
                    'project_name' => $project_name,
                    'serial_no'    => $item['serial_no'],
                    'remarks'      => $remarks
                ];

                // NEW: Add to Rented Audit Log
                $log_desc = "Bulk Assigned: " . $item['product_name'] . " (SN: " . $item['serial_no'] . ") to " . $target_user_name . " [Project: " . $project_name . "]";
                $stmtAudit->execute([$assigned_by, $inventory_id, $log_desc]);
            }
        }
    }

    $pdo->commit();

    /* =======================================================
        PDF GENERATION & EMAIL TRIGGER 
    ======================================================= */
    if (!empty($assigned_items_data)) {
        $stmtUser = $pdo->prepare("SELECT name, email, employee_id, department FROM users WHERE id = ?");
        $stmtUser->execute([$user_id]);
        $emp = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if ($emp) {
            $employee = [
                'name'          => $emp['name'],
                'code'          => $emp['employee_id'] ?? 'N/A',
                'department'    => $emp['department'] ?? 'N/A',
                'location'      => "{$city}, {$state}",
                'handover_date' => date('Y-m-d'),
                'handover_by'   => $_SESSION['name'] ?? 'Admin'
            ];

            $pdfPath = generateRentedHandoverPDF($employee, $assigned_items_data, "Good", $remarks);
            sendRentedHandoverMail($emp['email'], $pdfPath);
        }
    }

    header("Location: /rented_assign.php?success=1");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header("Location: /rented_assign.php?error=failed");
    exit;
}