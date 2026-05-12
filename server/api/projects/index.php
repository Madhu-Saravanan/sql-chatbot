<?php

require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../models/Project.php';

$userId = $_REQUEST['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $project  = new Project(getAppPDO());
        $projects = $project->getAllByUser($userId);
        echo json_encode(['projects' => $projects]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Server error']);
    }
    exit;
}

if ($method === 'POST') {
    $data   = json_decode(file_get_contents('php://input'), true);
    $name   = trim($data['name'] ?? '');
    $dbPort = (int)($data['db_port'] ?? 3306);
    $dbPass = $data['db_password'] ?? '';

    if (!$name) {
        http_response_code(422);
        echo json_encode(['error' => 'Project name is required', 'field' => 'name']);
        exit;
    }

    try {
        $project = new Project(getAppPDO());
        $newId   = $project->create($userId, [
            'name'                  => $name,
            'description'           => $data['description'] ?? null,
            'db_host'               => $data['db_host'] ?? null,
            'db_port'               => $dbPort,
            'db_name'               => $data['db_name'] ?? null,
            'db_user'               => $data['db_user'] ?? null,
            'db_password_encrypted' => $dbPass ? Project::encryptPassword($dbPass) : null,
        ]);
        $all     = $project->getAllByUser($userId);
        $newProj = null;
        foreach ($all as $p) { if ($p['id'] === $newId) { $newProj = $p; break; } }
        http_response_code(201);
        echo json_encode(['project' => $newProj]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
