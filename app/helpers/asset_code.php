<?php
function generateAssetCode($category_id)
{
    $pdo = db();

    // 1. Get prefix from inv_categories
    $stmt = $pdo->prepare("SELECT asset_prefix FROM inv_categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $prefix = $stmt->fetchColumn();

    if (!$prefix) {
        throw new Exception("Category prefix not found in inv_categories!");
    }

    // 2. Find last asset_code for this category prefix
    $stmt = $pdo->prepare("
        SELECT asset_code 
        FROM inventory_items
        WHERE asset_code LIKE ?
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute([$prefix . '%']);
    $last = $stmt->fetchColumn();

    // 3. Determine next number
    if ($last) {
        $num = intval(substr($last, strlen($prefix))) + 1;
    } else {
        $num = 1;
    }

    // 4. Build code ? TECHLAP001
    return $prefix . str_pad($num, 3, '0', STR_PAD_LEFT);
}
