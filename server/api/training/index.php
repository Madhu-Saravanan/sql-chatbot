<?php

require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../models/Training.php';
require_once __DIR__ . '/../../models/Project.php';

$userId = $_REQUEST['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

function verifyOwner(int $projectId, int $userId): void {
    if (!$projectId) { http_response_code(400); echo json_encode(['error' => 'project_id required']); exit; }
    $p = new Project(getAppPDO());
    if (!$p->findByIdAndUser($projectId, $userId)) {
        http_response_code(404); echo json_encode(['error' => 'Project not found']); exit;
    }
}

if ($method === 'GET') {
    $projectId = (int)($_GET['project_id'] ?? 0);
    verifyOwner($projectId, $userId);
    $status   = $_GET['status'] ?? null;
    $training = new Training(getAppPDO());
    echo json_encode(['pairs' => $training->getAllByProject($projectId, $status ?: null)]);
    exit;
}

if ($method === 'POST') {
    $data      = json_decode(file_get_contents('php://input'), true);
    $projectId = (int)($data['project_id'] ?? 0);
    verifyOwner($projectId, $userId);
    $question    = trim($data['question'] ?? '');
    $sqlQuery    = trim($data['sql_query'] ?? '');
    $explanation = trim($data['explanation'] ?? '');
    if (!$question || !$sqlQuery) {
        http_response_code(422); echo json_encode(['error' => 'question and sql_query are required']); exit;
    }
    $training = new Training(getAppPDO());
    $newId    = $training->create($projectId, $question, $sqlQuery, $explanation);
    http_response_code(201);
    echo json_encode(['id' => $newId, 'question' => $question, 'sql_query' => $sqlQuery, 'explanation' => $explanation, 'status' => 'pending']);
    exit;
}

http_response_code(405); echo json_encode(['error' => 'Method not allowed']);
