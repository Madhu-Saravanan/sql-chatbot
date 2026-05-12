<?php

class Training {
    public function __construct(private PDO $pdo) {}

    public function getAllByProject(int $projectId, ?string $status = null): array {
        if ($status) {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM training_pairs WHERE project_id = ? AND status = ? ORDER BY created_at DESC'
            );
            $stmt->execute([$projectId, $status]);
        } else {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM training_pairs WHERE project_id = ? ORDER BY created_at DESC'
            );
            $stmt->execute([$projectId]);
        }
        return $stmt->fetchAll();
    }

    public function findByIdAndProject(int $id, int $projectId): ?array {
        if ($projectId > 0) {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM training_pairs WHERE id = ? AND project_id = ? LIMIT 1'
            );
            $stmt->execute([$id, $projectId]);
        } else {
            $stmt = $this->pdo->prepare('SELECT * FROM training_pairs WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
        }
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(int $projectId, string $question, string $sqlQuery, string $explanation): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO training_pairs (project_id, question, sql_query, explanation) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$projectId, $question, $sqlQuery, $explanation]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updateStatus(int $id, string $status): bool {
        $stmt = $this->pdo->prepare('UPDATE training_pairs SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
        return $stmt->rowCount() > 0;
    }

    public function update(int $id, array $data): bool {
        $allowed = ['question', 'sql_query', 'explanation'];
        $sets    = [];
        $params  = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $sets[]          = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        if (empty($sets)) return false;
        $params[':id'] = $id;
        $stmt = $this->pdo->prepare('UPDATE training_pairs SET ' . implode(', ', $sets) . ' WHERE id = :id');
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    public function exportApproved(int $projectId): array {
        $stmt = $this->pdo->prepare(
            "SELECT question, sql_query, explanation FROM training_pairs
             WHERE project_id = ? AND status = 'approved' ORDER BY created_at ASC"
        );
        $stmt->execute([$projectId]);
        return $stmt->fetchAll();
    }
}
