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

if (($active !== 0 && $active !== 1) || $idCategory === false || $idCategory < 1
    || $name === '' || $image === '' || $price === false || $price < 0
    || $stock === false || $stock < 0) {
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

$idResult = $connection->query('SELECT COALESCE(MAX(product_id), 0) + 1 AS next_id FROM product');
$productId = (int) $idResult->fetch_assoc()['next_id'];
$sku = trim((string) ($data['sku'] ?? 'SKU-' . $productId));
if ($sku === '') {
    sendError('Product SKU cannot be empty.', 400);
}

$stmt = $connection->prepare('INSERT INTO product (product_id, sku, active, id_category, name, image, description, price, stock) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
$stmt->bind_param('isiisssdi', $productId, $sku, $active, $idCategory, $name, $image, $description, $price, $stock);
$stmt->execute();
$stmt->close();

$productStmt = $connection->prepare('SELECT product_id, sku, active, id_category, name, image, description, price, stock FROM product WHERE product_id = ? LIMIT 1');
$productStmt->bind_param('i', $productId);
$productStmt->execute();
$productRow = $productStmt->get_result()->fetch_assoc();
$productStmt->close();

sendJson($productRow, 201);
