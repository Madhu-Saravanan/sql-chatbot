<?php

require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../models/Training.php';
require_once __DIR__ . '/../../models/Project.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit;
}

$userId    = $_REQUEST['user_id'];
$projectId = (int)($_GET['project_id'] ?? 0);

if (!$projectId) { http_response_code(400); echo json_encode(['error' => 'project_id required']); exit; }

try {
    $appPdo  = getAppPDO();
    $project = new Project($appPdo);
    if (!$project->findByIdAndUser($projectId, $userId)) {
        http_response_code(404); echo json_encode(['error' => 'Project not found']); exit;
    }
    $training = new Training($appPdo);
    $pairs    = $training->exportApproved($projectId);
    $lines    = [];
    foreach ($pairs as $pair) {
        $lines[] = json_encode([
            'prompt'     => "Question: {$pair['question']}\nSQL:",
            'completion' => ' ' . $pair['sql_query'],
        ], JSON_UNESCAPED_UNICODE);
    }
    $filename = "training_{$projectId}_" . date('Ymd') . '.jsonl';
    header('Content-Type: application/x-ndjson');
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header('Cache-Control: no-cache');
    echo implode("\n", $lines);
} catch (Exception $e) {
    http_response_code(500); echo json_encode(['error' => 'Server error']);
}
