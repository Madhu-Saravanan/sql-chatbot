<?php

require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../models/Project.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit;
}

$userId    = $_REQUEST['user_id'];
$data      = json_decode(file_get_contents('php://input'), true);
$projectId = isset($data['project_id']) ? (int)$data['project_id'] : null;

$config = [
    'host'     => $data['db_host'] ?? '',
    'port'     => $data['db_port'] ?? 3306,
    'dbname'   => $data['db_name'] ?? '',
    'user'     => $data['db_user'] ?? '',
    'password' => $data['db_password'] ?? '',
];

if ($projectId) {
    $project = new Project(getAppPDO());
    $proj    = $project->findByIdAndUser($projectId, $userId);
    if (!$proj) { http_response_code(404); echo json_encode(['error' => 'Project not found']); exit; }
    $config = [
        'host'     => $proj['db_host'],
        'port'     => $proj['db_port'],
        'dbname'   => $proj['db_name'],
        'user'     => $proj['db_user'],
        'password' => $proj['db_password_encrypted'] ? Project::decryptPassword($proj['db_password_encrypted']) : '',
    ];
}

try {
    $pdo     = getProjectPDO($config);
    $version = $pdo->query('SELECT VERSION()')->fetchColumn();
    if ($projectId) { $project = new Project(getAppPDO()); $project->setConnected($projectId, true); }
    echo json_encode(['success' => true, 'version' => $version]);
} catch (PDOException $e) {
    if ($projectId) { $project = new Project(getAppPDO()); $project->setConnected($projectId, false); }
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
