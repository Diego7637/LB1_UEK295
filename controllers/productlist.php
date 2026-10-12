<?php

declare(strict_types=1);

require_once __DIR__ . '/../middleware/auth.php';

requireAuth();
require __DIR__ . '/product/list.php';
