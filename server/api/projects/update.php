<?php

require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../models/Project.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$id     = (int)($_GET['id'] ?? 0);
$userId = $_REQUEST['user_id'];

if (!$id) { http_response_code(400); echo json_encode(['error' => 'Project ID required']); exit; }

$data    = json_decode(file_get_contents('php://input'), true);
$project = new Project(getAppPDO());

if (!$project->findByIdAndUser($id, $userId)) {
    http_response_code(404); echo json_encode(['error' => 'Project not found']); exit;
}

$update = [];
foreach (['name','description','db_host','db_name','db_user'] as $f) {
    if (isset($data[$f])) $update[$f] = $data[$f];
}
if (isset($data['db_port']))     $update['db_port'] = (int)$data['db_port'];
if (!empty($data['db_password'])) $update['db_password_encrypted'] = Project::encryptPassword($data['db_password']);

try {
    $project->update($id, $userId, $update);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500); echo json_encode(['error' => 'Server error']);
}
