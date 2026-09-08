<?php
require_once __DIR__ . '/../app/db.php';

$pdo = db();

if (!isset($_POST['order'])) {
    echo "no-data";
    exit;
}

$order = $_POST['order']; // array of IDs
$pos = 0;

foreach ($order as $id) {
    $pdo->prepare("UPDATE dashboard_cards SET sort_order=? WHERE id=?")
        ->execute([$pos, $id]);
    $pos++;
}

echo "success";
