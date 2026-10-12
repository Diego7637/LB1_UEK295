<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';

$connection = connectDb();
$result = $connection->query('SELECT category_id, active, name FROM category ORDER BY category_id DESC');

$categories = [];
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

sendJson($categories, 200);
