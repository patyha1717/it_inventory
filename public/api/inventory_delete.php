<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/helpers/auth.php';

check_login();

$id = intval($_GET['id']);
$pdo = db();

if ($_SESSION['role'] !== 'superadmin') {
    echo json_encode(["error" => "denied"]); 
    exit;
}


$pdo->prepare("DELETE FROM inventory_items WHERE id=?")->execute([$id]);

header("Location: /inventory_list.php");
exit;
?>
