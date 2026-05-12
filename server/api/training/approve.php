<?php

require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../models/Training.php';
require_once __DIR__ . '/../../models/Project.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit;
}

$userId = $_REQUEST['user_id'];
$data   = json_decode(file_get_contents('php://input'), true);
$id     = (int)($data['id'] ?? 0);
$status = $data['status'] ?? '';

if (!$id || !in_array($status, ['approved','rejected','pending'], true)) {
    http_response_code(422); echo json_encode(['error' => 'id and valid status required']); exit;
}

try {
    $appPdo   = getAppPDO();
    $training = new Training($appPdo);
    $pair     = $training->findByIdAndProject($id, 0);
    if (!$pair) { http_response_code(404); echo json_encode(['error' => 'Training pair not found']); exit; }

    $project = new Project($appPdo);
    if (!$project->findByIdAndUser($pair['project_id'], $userId)) {
        http_response_code(403); echo json_encode(['error' => 'Forbidden']); exit;
    }

    $editData = [];
    foreach (['question','sql_query','explanation'] as $f) {
        if (isset($data[$f])) $editData[$f] = $data[$f];
    }
    if (!empty($editData)) $training->update($id, $editData);
    $training->updateStatus($id, $status);
    echo json_encode(['success' => true, 'id' => $id, 'status' => $status]);
} catch (Exception $e) {
    http_response_code(500); echo json_encode(['error' => 'Server error']);
}
