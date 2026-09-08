<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
auth();

$pdo = db();

$cat_id = intval($_GET['cat_id']);

$stmt = $pdo->prepare("
    SELECT id, item_details, serial_no
    FROM inventory_items
    WHERE category_id = ? AND status = 'available'
    ORDER BY item_details ASC
");
$stmt->execute([$cat_id]);

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($data);
