<?php

require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../models/Project.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit;
}

$userId    = $_REQUEST['user_id'];
$projectId = (int)($_GET['project_id'] ?? 0);

if (!$projectId) { http_response_code(400); echo json_encode(['error' => 'project_id required']); exit; }

try {
    $project = new Project(getAppPDO());
    $proj    = $project->findByIdAndUser($projectId, $userId);
    if (!$proj) { http_response_code(404); echo json_encode(['error' => 'Project not found']); exit; }

    $password = $proj['db_password_encrypted'] ? Project::decryptPassword($proj['db_password_encrypted']) : '';
    $pdo = getProjectPDO([
        'host' => $proj['db_host'], 'port' => $proj['db_port'],
        'dbname' => $proj['db_name'], 'user' => $proj['db_user'], 'password' => $password,
    ]);

    $stmt = $pdo->prepare(
        'SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_KEY, EXTRA
         FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ?
         ORDER BY TABLE_NAME, ORDINAL_POSITION'
    );
    $stmt->execute([$proj['db_name']]);

    $schema = [];
    foreach ($stmt->fetchAll() as $row) {
        $schema[$row['TABLE_NAME']][] = [
            'column'   => $row['COLUMN_NAME'],
            'type'     => $row['DATA_TYPE'],
            'nullable' => $row['IS_NULLABLE'] === 'YES',
            'key'      => $row['COLUMN_KEY'],
            'extra'    => $row['EXTRA'],
        ];
    }
    echo json_encode(['schema' => $schema]);
} catch (PDOException $e) {
    http_response_code(400); echo json_encode(['error' => 'Cannot connect: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500); echo json_encode(['error' => 'Server error']);
}
