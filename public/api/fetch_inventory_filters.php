<?php
require_once __DIR__ . '/../../app/db.php';

$pdo = db();

$category = $_GET['category'] ?? "";
$brand = $_GET['brand'] ?? "";

$response = ["brands" => [], "models" => []];

if ($category) {
    // Get brands
    $stmt = $pdo->prepare("
        SELECT DISTINCT brand FROM inventory_items 
        WHERE category_id=? AND status='available'
        ORDER BY brand ASC
    ");
    $stmt->execute([$category]);
    $response["brands"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($category && $brand) {
    // Get models
    $stmt = $pdo->prepare("
        SELECT DISTINCT model FROM inventory_items 
        WHERE category_id=? AND brand=? AND status='available'
        ORDER BY model ASC
    ");
    $stmt->execute([$category, $brand]);
    $response["models"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode($response);
