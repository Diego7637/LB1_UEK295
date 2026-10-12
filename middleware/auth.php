<?php

declare(strict_types=1);

require_once __DIR__ . '/../helpers/response.php';

function getJwtSecret(): string
{
    $secret = getenv('JWT_SECRET');
    if ($secret === false || $secret === '') {
        return 'backend-api-secret-change-me';
    }

    return $secret;
}

function base64UrlDecode(string $value): string|false
{
    $padding = (4 - strlen($value) % 4) % 4;
    return base64_decode(strtr($value, '-_', '+/') . str_repeat('=', $padding), true);
}

function createJwt(array $payload): string
{
    $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
    $encodedPayload = json_encode($payload);
    if ($header === false || $encodedPayload === false) {
        throw new RuntimeException('Could not encode JWT data.');
    }

    $encodedHeader = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
    $encodedPayload = rtrim(strtr(base64_encode($encodedPayload), '+/', '-_'), '=');
    $signingInput = $encodedHeader . '.' . $encodedPayload;
    $signature = hash_hmac('sha256', $signingInput, getJwtSecret(), true);
    $encodedSignature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

    return $signingInput . '.' . $encodedSignature;
}

function verifyJwt(string $token): ?array
{
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return null;
    }

    [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;
    $headerJson = base64UrlDecode($encodedHeader);
    $payloadJson = base64UrlDecode($encodedPayload);
    $signature = base64UrlDecode($encodedSignature);

    if ($headerJson === false || $payloadJson === false || $signature === false) {
        return null;
    }

    $header = json_decode($headerJson, true);
    $payload = json_decode($payloadJson, true);
    if (!is_array($header) || ($header['alg'] ?? null) !== 'HS256' || !is_array($payload)) {
        return null;
    }

    $expectedSignature = hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, getJwtSecret(), true);
    if (!hash_equals($expectedSignature, $signature)) {
        return null;
    }

    if (!isset($payload['exp']) || !is_numeric($payload['exp']) || time() >= (int) $payload['exp']) {
        return null;
    }

    return $payload;
}

function requireAuth(): array
{
    $authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if ($authorization === '' && function_exists('getallheaders')) {
        $headers = getallheaders();
        $authorization = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }

    if (!preg_match('/^Bearer\s+(\S+)$/i', trim($authorization), $matches)) {
        sendError('Unauthorized. A bearer token is required.', 401);
    }

    $payload = verifyJwt($matches[1]);
    if ($payload === null) {
        sendError('Unauthorized. The bearer token is invalid or expired.', 401);
    }

    return $payload;
}