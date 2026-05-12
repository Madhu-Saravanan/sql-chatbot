<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function jwtEncode(array $payload): string {
    return JWT::encode($payload, getenv('JWT_SECRET'), 'HS256');
}

function jwtDecode(string $token): object {
    return JWT::decode($token, new Key(getenv('JWT_SECRET'), 'HS256'));
}
