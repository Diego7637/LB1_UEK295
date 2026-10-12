<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/json.php';
require_once __DIR__ . '/../../helpers/response.php';

$data = readJsonBody();
$name = trim((string) ($data['name'] ?? ''));
$active = filter_var($data['active'] ?? 1, FILTER_VALIDATE_INT);

if ($name === '' || ($active !== 0 && $active !== 1)) {
    sendError('Category name and a valid active value (0 or 1) are required.', 400);
}

$connection = connectDb();
$idResult = $connection->query('SELECT COALESCE(MAX(category_id), 0) + 1 AS next_id FROM category');
$categoryId = (int) $idResult->fetch_assoc()['next_id'];

$stmt = $connection->prepare('INSERT INTO category (category_id, active, name) VALUES (?, ?, ?)');
$stmt->bind_param('iis', $categoryId, $active, $name);
$stmt->execute();
$stmt->close();

sendJson([
    'category_id' => $categoryId,
    'active' => $active,
    'name' => $name,
], 201);
