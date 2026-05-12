<?php

require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/claude.php';
require_once __DIR__ . '/../../models/Chat.php';
require_once __DIR__ . '/../../models/Project.php';
require_once __DIR__ . '/../../models/Training.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit;
}

$userId  = $_REQUEST['user_id'];
$data    = json_decode(file_get_contents('php://input'), true);
$projId  = (int)($data['project_id'] ?? 0);
$message = trim($data['message'] ?? '');

if (!$projId || !$message) {
    http_response_code(422); echo json_encode(['error' => 'project_id and message are required']); exit;
}

try {
    $appPdo  = getAppPDO();
    $project = new Project($appPdo);
    $proj    = $project->findByIdAndUser($projId, $userId);
    if (!$proj) { http_response_code(404); echo json_encode(['error' => 'Project not found']); exit; }

    $password = $proj['db_password_encrypted'] ? Project::decryptPassword($proj['db_password_encrypted']) : '';
    try {
        $projectPdo = getProjectPDO([
            'host' => $proj['db_host'], 'port' => $proj['db_port'],
            'dbname' => $proj['db_name'], 'user' => $proj['db_user'], 'password' => $password,
        ]);
    } catch (PDOException $e) {
        http_response_code(400); echo json_encode(['error' => 'Cannot connect to project database: ' . $e->getMessage()]); exit;
    }

    // Build schema text
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
        $key      = $col['COLUMN_KEY'] === 'PRI' ? ' PK' : ($col['COLUMN_KEY'] === 'MUL' ? ' FK' : '');
        $nullable = $col['IS_NULLABLE'] === 'YES' ? '' : ' NOT NULL';
        $schemaText .= "  - {$col['COLUMN_NAME']} ({$col['DATA_TYPE']}){$key}{$nullable}\n";
    }

    // Training examples
    $training      = new Training($appPdo);
    $approved      = $training->exportApproved($projId);
    $trainingBlock = '';
    if (!empty($approved)) {
        $trainingBlock = "\n\nExamples of correct queries for this database:\n";
        foreach ($approved as $pair) {
            $trainingBlock .= "Q: {$pair['question']}\nSQL: {$pair['sql_query']}\n\n";
        }
    }

    $systemPrompt = "You are a MySQL expert assistant. When users ask questions about their database, respond with:\n1. A plain-English explanation\n2. The SQL query in a ```sql ... ``` code block\n3. A brief explanation of the query logic\n\nDatabase Schema:{$schemaText}{$trainingBlock}\n\nRules:\n- Only generate SELECT queries unless explicitly asked for INSERT/UPDATE/DELETE\n- Add LIMIT 1000 to queries that may return many rows\n- Never generate DROP or TRUNCATE\n- If a question cannot be answered with SQL, explain why";

    // Chat history
    $chat     = new Chat($appPdo);
    $history  = $chat->getHistory($projId, 20);
    $messages = [];
    foreach ($history as $h) {
        $messages[] = ['role' => $h['role'] === 'bot' ? 'assistant' : 'user', 'content' => $h['message']];
    }
    $messages[] = ['role' => 'user', 'content' => $message];

    $start    = microtime(true);
    $response = callClaude($systemPrompt, $messages);
    $responseTimeMs = (int)((microtime(true) - $start) * 1000);

    $botText     = $response['content'];
    $sqlQuery    = null;
    $queryResult = null;

    if (preg_match('/```sql\s*([\s\S]*?)```/i', $botText, $matches)) {
        $sqlQuery = trim($matches[1]);
        if (preg_match('/^\s*SELECT/i', $sqlQuery)) {
            try {
                $stmt = $projectPdo->prepare($sqlQuery);
                $stmt->execute();
                $queryResult = array_slice($stmt->fetchAll(), 0, 1000);
            } catch (PDOException $e) {
                $queryResult = ['error' => $e->getMessage()];
            }
        } else {
            $queryResult = ['error' => 'Non-SELECT queries are shown but not executed automatically for safety.'];
        }
    }

    $chat->save(['project_id' => $projId, 'role' => 'user', 'message' => $message]);
    $chat->save([
        'project_id' => $projId, 'role' => 'bot', 'message' => $botText,
        'sql_query' => $sqlQuery, 'query_result' => $queryResult,
        'tokens_input' => $response['input_tokens'], 'tokens_output' => $response['output_tokens'],
        'response_time_ms' => $responseTimeMs,
    ]);

    echo json_encode([
        'message' => $botText, 'sql_query' => $sqlQuery, 'query_result' => $queryResult,
        'tokens_input' => $response['input_tokens'], 'tokens_output' => $response['output_tokens'],
        'response_time_ms' => $responseTimeMs,
    ], JSON_UNESCAPED_UNICODE);

} catch (RuntimeException $e) {
    http_response_code(502); echo json_encode(['error' => 'AI service error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500); echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
