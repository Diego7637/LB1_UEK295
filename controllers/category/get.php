<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';

$connection = connectDb();
$stmt = $connection->prepare('SELECT category_id, active, name FROM category WHERE category_id = ? LIMIT 1');
$stmt->bind_param('i', $category_id);
$stmt->execute();
$category = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($category === null) {
    sendError('Category not found.', 404);
}

sendJson($category, 200);
