<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/json.php';
require_once __DIR__ . '/../../helpers/response.php';

$data = readJsonBody();
$requiredFields = ['active', 'id_category', 'name', 'image', 'description', 'price', 'stock'];
foreach ($requiredFields as $field) {
    if (!array_key_exists($field, $data)) {
        sendError("Missing required product field: {$field}.", 400);
    }
}

$active = filter_var($data['active'], FILTER_VALIDATE_INT);
$idCategory = filter_var($data['id_category'], FILTER_VALIDATE_INT);
$name = trim((string) $data['name']);
$image = trim((string) $data['image']);
$description = (string) $data['description'];
$price = filter_var($data['price'], FILTER_VALIDATE_FLOAT);
$stock = filter_var($data['stock'], FILTER_VALIDATE_INT);
$sku = trim((string) ($data['sku'] ?? 'SKU-' . $product_id));

if (($active !== 0 && $active !== 1) || $idCategory === false || $idCategory < 1
    || $name === '' || $image === '' || $price === false || $price < 0
    || $stock === false || $stock < 0 || $sku === '') {
    sendError('Product fields contain invalid values.', 400);
}

$connection = connectDb();
$categoryStmt = $connection->prepare('SELECT category_id FROM category WHERE category_id = ? LIMIT 1');
$categoryStmt->bind_param('i', $idCategory);
$categoryStmt->execute();
if ($categoryStmt->get_result()->num_rows === 0) {
    sendError('Referenced category does not exist.', 400);
}
$categoryStmt->close();

$checkStmt = $connection->prepare('SELECT product_id FROM product WHERE product_id = ? LIMIT 1');
$checkStmt->bind_param('i', $product_id);
$checkStmt->execute();
$exists = $checkStmt->get_result()->num_rows > 0;
$checkStmt->close();

if ($exists) {
    $stmt = $connection->prepare('UPDATE product SET sku = ?, active = ?, id_category = ?, name = ?, image = ?, description = ?, price = ?, stock = ? WHERE product_id = ?');
    $stmt->bind_param('siisssdii', $sku, $active, $idCategory, $name, $image, $description, $price, $stock, $product_id);
} else {
    $stmt = $connection->prepare('INSERT INTO product (sku, active, id_category, name, image, description, price, stock, product_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('siisssdii', $sku, $active, $idCategory, $name, $image, $description, $price, $stock, $product_id);
}
$stmt->execute();
$stmt->close();

$productStmt = $connection->prepare('SELECT product_id, sku, active, id_category, name, image, description, price, stock FROM product WHERE product_id = ? LIMIT 1');
$productStmt->bind_param('i', $product_id);
$productStmt->execute();
$product = $productStmt->get_result()->fetch_assoc();
$productStmt->close();

sendJson($product, $exists ? 200 : 201);
