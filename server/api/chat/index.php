<?php

require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../models/Chat.php';
require_once __DIR__ . '/../../models/Project.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit;
}

$userId    = $_REQUEST['user_id'];
$projectId = (int)($_GET['project_id'] ?? 0);
$limit     = min((int)($_GET['limit'] ?? 50), 200);

if (!$projectId) { http_response_code(400); echo json_encode(['error' => 'project_id required']); exit; }

try {
    $project = new Project(getAppPDO());
    $proj    = $project->findByIdAndUser($projectId, $userId);
    if (!$proj) {
        http_response_code(404); echo json_encode(['error' => 'Project not found']); exit;
    }
    $chat  = new Chat(getAppPDO());
    $chats = $chat->getHistory($projectId, $limit);
    echo json_encode(['chats' => $chats, 'project' => ['id' => $proj['id'], 'name' => $proj['name']]]);
} catch (Exception $e) {
    http_response_code(500); echo json_encode(['error' => 'Server error']);
}
