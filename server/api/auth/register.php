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
$name     = trim($data['name'] ?? '');
$email    = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

if (strlen($name) < 2 || strlen($name) > 100) {
    http_response_code(422);
    echo json_encode(['error' => 'Name must be between 2 and 100 characters', 'field' => 'name']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['error' => 'Invalid email address', 'field' => 'email']);
    exit;
}
if (strlen($password) < 8) {
    http_response_code(422);
    echo json_encode(['error' => 'Password must be at least 8 characters', 'field' => 'password']);
    exit;
}

try {
    $user = new User(getAppPDO());
    if ($user->emailExists($email)) {
        http_response_code(409);
        echo json_encode(['error' => 'Email already registered']);
        exit;
    }
    $hash  = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $newId = $user->create($name, $email, $hash);
    $expiry = (int)(getenv('JWT_EXPIRY') ?: 86400);
    $token  = jwtEncode([
        'sub'   => $newId,
        'email' => $email,
        'iat'   => time(),
        'exp'   => time() + $expiry,
    ]);
    http_response_code(201);
    echo json_encode(['token' => $token, 'user' => ['id' => $newId, 'name' => $name, 'email' => $email]]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
