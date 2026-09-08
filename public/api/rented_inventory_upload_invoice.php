<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/helpers/auth.php';

check_login();
$pdo = db();

$id = $_POST['id'] ?? 0;

if (!$id || empty($_FILES['invoice']['name'])) {
    echo json_encode(['status'=>'error','message'=>'Invalid request']);
    exit;
}

$dir = __DIR__ . '/../uploads/rented_invoices/';
if (!is_dir($dir)) mkdir($dir, 0755, true);

$ext = pathinfo($_FILES['invoice']['name'], PATHINFO_EXTENSION);
$name = 'rented_'.$id.'_'.time().'.'.$ext;
$path = $dir.$name;

if (move_uploaded_file($_FILES['invoice']['tmp_name'], $path)) {

    $dbPath = '/uploads/rented_invoices/'.$name;

    $pdo->prepare("
        UPDATE rented_inventory 
        SET invoice_file = ? 
        WHERE id = ?
    ")->execute([$dbPath, $id]);

    echo json_encode(['status'=>'success']);
} else {
    echo json_encode(['status'=>'error','message'=>'Upload failed']);
}
