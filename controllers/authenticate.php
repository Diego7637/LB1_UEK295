<?php

declare(strict_types=1);

require_once __DIR__ . '/../helpers/json.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../middleware/auth.php';

$data = readJsonBody();
$username = trim((string) ($data['username'] ?? ''));
$password = (string) ($data['password'] ?? '');

if ($username === '' || $password === '') {
    sendError('Username and password are required.', 400);
}

$expectedUsername = getenv('API_USERNAME') ?: 'admin';
$expectedPassword = getenv('API_PASSWORD') ?: 'sec!ReT423*&';

if (!hash_equals($expectedUsername, $username) || !hash_equals($expectedPassword, $password)) {
    sendError('Invalid username or password.', 401);
}

$issuedAt = time();
$expiresAt = $issuedAt + 3600;
$token = createJwt([
    'sub' => $username,
    'username' => $username,
    'iat' => $issuedAt,
    'exp' => $expiresAt,
]);

sendJson([
    'token' => $token,
    'expires_in' => 3600,
    'user' => [
        'username' => $username,
    ],
]);