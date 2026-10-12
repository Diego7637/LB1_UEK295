<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';

$connection = connectDb();
$stmt = $connection->prepare('SELECT product_id, sku, active, id_category, name, image, description, price, stock FROM product WHERE product_id = ? LIMIT 1');
$stmt->bind_param('i', $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if ($product === null) {
    sendError('Product not found.', 404);
}

sendJson($product, 200);
