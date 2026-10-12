<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/json.php';
require_once __DIR__ . '/../../helpers/response.php';

$data = readJsonBody();
$updates = [];
$types = '';
$params = [];

if (array_key_exists('active', $data)) {
    $active = filter_var($data['active'], FILTER_VALIDATE_INT);
    if ($active !== 0 && $active !== 1) {
        sendError('Category active value must be 0 or 1.', 400);
    }
    $updates[] = 'active = ?';
    $types .= 'i';
    $params[] = $active;
}

if (array_key_exists('name', $data)) {
    $name = trim((string) $data['name']);
    if ($name === '') {
        sendError('Category name cannot be empty.', 400);
    }
    $updates[] = 'name = ?';
    $types .= 's';
    $params[] = $name;
}

if ($updates === []) {
    sendError('No valid category fields to update.', 400);
}

$connection = connectDb();
$params[] = $category_id;
$types .= 'i';
$stmt = $connection->prepare('UPDATE category SET ' . implode(', ', $updates) . ' WHERE category_id = ?');
$stmt->bind_param($types, ...$params);
$stmt->execute();
$stmt->close();

$categoryStmt = $connection->prepare('SELECT category_id, active, name FROM category WHERE category_id = ? LIMIT 1');
$categoryStmt->bind_param('i', $category_id);
$categoryStmt->execute();
$category = $categoryStmt->get_result()->fetch_assoc();
$categoryStmt->close();

if ($category === null) {
    sendError('Category not found.', 404);
}

sendJson($category, 200);
