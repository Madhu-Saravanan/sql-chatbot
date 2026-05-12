<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/jwt.php';
require_once __DIR__ . '/../../models/User.php';

setCorsHeaders();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data     = json_decode(file_get_contents('php://input'), true);
$email    = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

if (!$email || !$password) {
    http_response_code(422);
    echo json_encode(['error' => 'Email and password are required']);
    exit;
}

try {
    $user = new User(getAppPDO());
    $row  = $user->findByEmail($email);
    if (!$row || !password_verify($password, $row['password_hash'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
        exit;
    }
    $expiry = (int)(getenv('JWT_EXPIRY') ?: 86400);
    $token  = jwtEncode([
        'sub'   => $row['id'],
        'email' => $row['email'],
        'iat'   => time(),
        'exp'   => time() + $expiry,
    ]);
    echo json_encode(['token' => $token, 'user' => ['id' => $row['id'], 'name' => $row['name'], 'email' => $row['email']]]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
