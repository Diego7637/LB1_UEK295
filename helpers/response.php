<?php

declare(strict_types=1);

final class ApiResponseException extends RuntimeException
{
    public function __construct(
        public readonly array $payload,
        public readonly int $status
    ) {
        parent::__construct('API response');
    }
}

function sendJson(array $payload, int $status = 200): never
{
    throw new ApiResponseException($payload, $status);
}

function sendError(string $message, int $status, array $details = []): void
{
    $payload = ['error' => $message];
    if ($details !== []) {
        $payload['details'] = $details;
    }

    sendJson($payload, $status);
}
