<?php
require_once __DIR__ . '/../../app/db.php';

$pdo = db();

$category = $_GET['category'] ?? "";
$brand = $_GET['brand'] ?? "";
$model = $_GET['model'] ?? "";

$sql = "SELECT id, item_details, model, serial_no 
        FROM inventory_items 
        WHERE status='available'";

$params = [];

if ($category) { $sql .= " AND category_id=?";  $params[] = $category; }
if ($brand)    { $sql .= " AND brand=?";       $params[] = $brand; }
if ($model)    { $sql .= " AND model=?";       $params[] = $model; }

$sql .= " ORDER BY item_details ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

echo json_encode(["items" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
