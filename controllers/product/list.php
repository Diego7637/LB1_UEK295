<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';

$connection = connectDb();
$result = $connection->query(<<<'SQL'
    SELECT
        p.product_id,
        p.sku,
        p.active,
        p.id_category,
        p.name,
        p.image,
        p.description,
        p.price,
        p.stock
    FROM product p
    ORDER BY p.product_id DESC
SQL);

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

sendJson($products, 200);
