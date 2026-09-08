<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/mailer.php';   // Needed for PHPMailer + env()

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

check_login();
if (!is_admin()) {
    die("Unauthorized");
}

$pdo = db();

$assign_id = intval($_POST['assign_id']);
$remarks   = $_POST['remarks'] ?? "";

/* -----------------------------------------------------
   DEBUG LOG
------------------------------------------------------ */
$logFile = __DIR__ . "/unassign_debug.log";
file_put_contents($logFile, "\n== POST (" . date("Y-m-d H:i:s") . ") ==\n", FILE_APPEND);
file_put_contents($logFile, print_r($_POST, true), FILE_APPEND);

/* -----------------------------------------------------
   FETCH ASSIGNMENT DETAILS
------------------------------------------------------ */
$st = $pdo->prepare("
    SELECT a.*, 
           u.id AS uid, u.name, u.email,
           i.id AS iid, i.item_details, i.asset_code, i.serial_no
    FROM inventory_assignments a
    LEFT JOIN users u ON a.user_id = u.id
    LEFT JOIN inventory_items i ON a.item_id = i.id
    WHERE a.id = ?
");
$st->execute([$assign_id]);
$data = $st->fetch(PDO::FETCH_ASSOC);

file_put_contents($logFile, "\n== DB RESULT ==\n".print_r($data, true), FILE_APPEND);

if (!$data) {
    file_put_contents($logFile, "ERROR: Invalid assignment\n", FILE_APPEND);
    die("Invalid Assignment");
}

/* -----------------------------------------------------
   UPDATE ITEM ? AVAILABLE
------------------------------------------------------ */
$pdo->prepare("UPDATE inventory_items SET status='available' WHERE id=?")
    ->execute([$data['item_id']]);

/* -----------------------------------------------------
   UPDATE ASSIGNMENT ? UNASSIGNED
------------------------------------------------------ */
$pdo->prepare("
    UPDATE inventory_assignments 
    SET unassigned_on = NOW(), remarks=?
    WHERE id = ?
")->execute([$remarks, $assign_id]);

/* -----------------------------------------------------
   INSERT AUDIT LOG
------------------------------------------------------ */
$description = "{$data['item_details']} ({$data['asset_code']}) unassigned from {$data['name']}";

$pdo->prepare("
    INSERT INTO audit_log (user_id, asset_id, action, description, created_at)
    VALUES (?, ?, 'unassign', ?, NOW())
")->execute([
    $data['uid'],
    $data['iid'],
    $description
]);

file_put_contents($logFile, "\n== AUDIT INSERTED ==\n$description\n", FILE_APPEND);

/* -----------------------------------------------------
   SEND EMAIL (PHPMailer)
------------------------------------------------------ */
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = envv('SMTP_HOST');
    $mail->SMTPAuth   = true;
    $mail->Username   = envv('SMTP_USER');
    $mail->Password   = envv('SMTP_PASS');
    $mail->SMTPSecure = envv('SMTP_SECURE','tls');
    $mail->Port       = envv('SMTP_PORT',587);
    $mail->isHTML(true);

    $mail->setFrom(envv('MAIL_FROM_ADDRESS'), envv('MAIL_FROM_NAME'));
    $mail->addAddress($data['email']);
    $mail->Subject = "Asset Unassigned From You";

    $mail->Body = "
        Hello {$data['name']},<br><br>
        The following asset has been unassigned:<br><br>

        <b>Item:</b> {$data['item_details']}<br>
        <b>Asset Code:</b> {$data['asset_code']}<br>
        <b>Serial No:</b> {$data['serial_no']}<br><br>

        <b>Remarks:</b> {$remarks}<br><br>

        Regards,<br>
        IT Assets Team
    ";

    $mail->send();

    file_put_contents($logFile, "== MAIL SENT ==\n", FILE_APPEND);

} catch (Exception $e) {
    file_put_contents($logFile, "MAIL ERROR: ".$e->getMessage()."\n", FILE_APPEND);
}

/* -----------------------------------------------------
   REDIRECT
------------------------------------------------------ */
header("Location: /assigned_list.php?msg=Unassigned Successfully");
exit;

