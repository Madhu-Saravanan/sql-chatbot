<?php

class Chat {
    public function __construct(private PDO $pdo) {}

    public function getHistory(int $projectId, int $limit = 50): array {
        $stmt = $this->pdo->prepare(
            'SELECT id, role, message, sql_query, query_result, tokens_input, tokens_output, response_time_ms, created_at
             FROM chats WHERE project_id = ? ORDER BY created_at ASC LIMIT ?'
        );
        $stmt->execute([$projectId, $limit]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            if ($row['query_result'] !== null) {
                $row['query_result'] = json_decode($row['query_result'], true);
            }
        }
        return $rows;
    }

    public function save(array $data): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO chats (project_id, role, message, sql_query, query_result, tokens_input, tokens_output, response_time_ms)
             VALUES (:project_id, :role, :message, :sql_query, :query_result, :tokens_input, :tokens_output, :response_time_ms)'
        );
        $stmt->execute([
            ':project_id'       => $data['project_id'],
            ':role'             => $data['role'],
            ':message'          => $data['message'],
            ':sql_query'        => $data['sql_query'] ?? null,
            ':query_result'     => isset($data['query_result']) ? json_encode($data['query_result'], JSON_UNESCAPED_UNICODE) : null,
            ':tokens_input'     => $data['tokens_input'] ?? null,
            ':tokens_output'    => $data['tokens_output'] ?? null,
            ':response_time_ms' => $data['response_time_ms'] ?? null,
        ]);
        return (int)$this->pdo->lastInsertId();
    }
}
