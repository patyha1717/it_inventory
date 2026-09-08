<?php
require_once __DIR__ . '/../../app/db.php';

$pdo = db();

$sql = "
SELECT 
    i.id,
    i.category_id,
    i.asset_code,
    i.item_details,
    i.brand,
    i.model,
    i.serial_no,
    i.purchase_date,
    i.invoice_no,
    i.warranty_date,
    i.cost,
    i.status,
    i.invoice_file,      -- ADD THIS LINE
    i.created_at,
    c.name AS category_name
FROM inventory_items i
LEFT JOIN inv_categories c ON i.category_id = c.id
ORDER BY i.id DESC
";

$data = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($data);
?>
