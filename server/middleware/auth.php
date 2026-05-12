<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/jwt.php';

setCorsHeaders();
header('Content-Type: application/json');

function getToken(): ?string {
    $headers = [];
    if (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
    }
    $auth = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    if (!$auth && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $auth = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }
    if ($auth && str_starts_with($auth, 'Bearer ')) {
        return substr($auth, 7);
    }
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET['token'])) {
        return $_GET['token'];
    }
    return null;
}

$token = getToken();
if (!$token) {
    http_response_code(401);
    echo json_encode(['error' => 'No token provided']);
    exit;
}

try {
    $decoded = jwtDecode($token);
    $_REQUEST['user_id']    = (int)$decoded->sub;
    $_REQUEST['user_email'] = $decoded->email;
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid or expired token']);
    exit;
}
