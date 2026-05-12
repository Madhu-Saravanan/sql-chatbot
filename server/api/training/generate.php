<?php

require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/claude.php';
require_once __DIR__ . '/../../models/Training.php';
require_once __DIR__ . '/../../models/Project.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit;
}

$userId   = $_REQUEST['user_id'];
$data     = json_decode(file_get_contents('php://input'), true);
$projId   = (int)($data['project_id'] ?? 0);
$question = trim($data['question'] ?? '');

if (!$projId || !$question) {
    http_response_code(422); echo json_encode(['error' => 'project_id and question are required']); exit;
}

try {
    $appPdo  = getAppPDO();
    $project = new Project($appPdo);
    $proj    = $project->findByIdAndUser($projId, $userId);
    if (!$proj) { http_response_code(404); echo json_encode(['error' => 'Project not found']); exit; }

    $password   = $proj['db_password_encrypted'] ? Project::decryptPassword($proj['db_password_encrypted']) : '';
    $projectPdo = getProjectPDO([
        'host' => $proj['db_host'], 'port' => $proj['db_port'],
        'dbname' => $proj['db_name'], 'user' => $proj['db_user'], 'password' => $password,
    ]);

    $schemaStmt = $projectPdo->prepare(
        'SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_KEY
         FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ?
         ORDER BY TABLE_NAME, ORDINAL_POSITION'
    );
    $schemaStmt->execute([$proj['db_name']]);
    $schemaText   = '';
    $currentTable = '';
    foreach ($schemaStmt->fetchAll() as $col) {
        if ($col['TABLE_NAME'] !== $currentTable) {
            $currentTable = $col['TABLE_NAME'];
            $schemaText  .= "\nTable: $currentTable\n";
        }
        $key = $col['COLUMN_KEY'] === 'PRI' ? ' PK' : ($col['COLUMN_KEY'] === 'MUL' ? ' FK' : '');
        $schemaText .= "  - {$col['COLUMN_NAME']} ({$col['DATA_TYPE']}){$key}\n";
    }

    $systemPrompt = "You are a SQL expert generating training data.\nGiven the database schema below, generate a precise MySQL SELECT query for the user's question.\n\nDatabase Schema:{$schemaText}\n\nRespond with ONLY:\n1. The SQL query in a ```sql ... ``` code block\n2. A single sentence explaining what the query does";

    $response = callClaude($systemPrompt, [['role' => 'user', 'content' => $question]]);
    $botText  = $response['content'];

    $sqlQuery    = '';
    $explanation = '';
    if (preg_match('/```sql\s*([\s\S]*?)```/i', $botText, $matches)) {
        $sqlQuery = trim($matches[1]);
    }
    $explanation = trim(preg_replace('/```sql[\s\S]*?```/i', '', $botText));

    $training = new Training($appPdo);
    $newId    = $training->create($projId, $question, $sqlQuery ?: $botText, $explanation);

    http_response_code(201);
    echo json_encode(['id' => $newId, 'question' => $question, 'sql_query' => $sqlQuery ?: $botText, 'explanation' => $explanation, 'status' => 'pending']);
} catch (PDOException $e) {
    http_response_code(400); echo json_encode(['error' => 'Cannot connect to project database']);
} catch (RuntimeException $e) {
    http_response_code(502); echo json_encode(['error' => 'AI service error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500); echo json_encode(['error' => 'Server error']);
}
