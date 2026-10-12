<?php

declare(strict_types=1);

function readJsonBody(): array
{
    if (isset($GLOBALS['apiRequestBody'])) {
        return $GLOBALS['apiRequestBody'];
    }

    $rawBody = file_get_contents('php://input');
    if ($rawBody === false || trim($rawBody) === '') {
        return [];
    }

    $decoded = json_decode($rawBody, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
        sendError('Request body must contain a valid JSON object.', 400);
    }

    return $decoded;
}