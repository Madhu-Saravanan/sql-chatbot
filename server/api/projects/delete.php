<?php

require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../models/Project.php';

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit;
}

$id     = (int)($_GET['id'] ?? 0);
$userId = $_REQUEST['user_id'];

if (!$id) { http_response_code(400); echo json_encode(['error' => 'Project ID required']); exit; }

try {
    $project = new Project(getAppPDO());
    if (!$project->delete($id, $userId)) {
        http_response_code(404); echo json_encode(['error' => 'Project not found']); exit;
    }
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500); echo json_encode(['error' => 'Server error']);
}
