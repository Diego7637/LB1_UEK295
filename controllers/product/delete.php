<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';

$connection = connectDb();
$stmt = $connection->prepare('DELETE FROM product WHERE product_id = ?');
$stmt->bind_param('i', $product_id);
$stmt->execute();
$deleted = $stmt->affected_rows > 0;
$stmt->close();

if (!$deleted) {
    sendError('Product not found.', 404);
}

sendJson(['message' => 'Product deleted successfully.'], 200);
