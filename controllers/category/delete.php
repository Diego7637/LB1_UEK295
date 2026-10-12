<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';

$connection = connectDb();
$productStmt = $connection->prepare('SELECT product_id FROM product WHERE id_category = ? LIMIT 1');
$productStmt->bind_param('i', $category_id);
$productStmt->execute();
$hasProducts = $productStmt->get_result()->num_rows > 0;
$productStmt->close();

if ($hasProducts) {
    sendError('Cannot delete a category that is referenced by a product.', 409);
}

$stmt = $connection->prepare('DELETE FROM category WHERE category_id = ?');
$stmt->bind_param('i', $category_id);
$stmt->execute();
$deleted = $stmt->affected_rows > 0;
$stmt->close();

if (!$deleted) {
    sendError('Category not found.', 404);
}

sendJson(['message' => 'Category deleted successfully.'], 200);
