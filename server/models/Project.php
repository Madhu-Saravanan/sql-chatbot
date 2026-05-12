<?php

class Project {
    public function __construct(private PDO $pdo) {}

    public function getAllByUser(int $userId): array {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, description, db_host, db_port, db_name, db_user, is_connected, created_at
             FROM projects WHERE user_id = ? ORDER BY created_at DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM projects WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByIdAndUser(int $id, int $userId): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM projects WHERE id = ? AND user_id = ? LIMIT 1');
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(int $userId, array $data): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO projects (user_id, name, description, db_host, db_port, db_name, db_user, db_password_encrypted)
             VALUES (:user_id, :name, :description, :db_host, :db_port, :db_name, :db_user, :db_password_encrypted)'
        );
        $stmt->execute([
            ':user_id'               => $userId,
            ':name'                  => $data['name'],
            ':description'           => $data['description'] ?? null,
            ':db_host'               => $data['db_host'] ?? null,
            ':db_port'               => $data['db_port'] ?? 3306,
            ':db_name'               => $data['db_name'] ?? null,
            ':db_user'               => $data['db_user'] ?? null,
            ':db_password_encrypted' => $data['db_password_encrypted'] ?? null,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, int $userId, array $data): bool {
        $allowed = ['name','description','db_host','db_port','db_name','db_user','db_password_encrypted'];
        $sets = [];
        $params = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $sets[]          = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        if (empty($sets)) return false;
        $params[':id']      = $id;
        $params[':user_id'] = $userId;
        $stmt = $this->pdo->prepare(
            'UPDATE projects SET ' . implode(', ', $sets) . ' WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id, int $userId): bool {
        $stmt = $this->pdo->prepare('DELETE FROM projects WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        return $stmt->rowCount() > 0;
    }

    public function setConnected(int $id, bool $status): void {
        $stmt = $this->pdo->prepare('UPDATE projects SET is_connected = ? WHERE id = ?');
        $stmt->execute([$status ? 1 : 0, $id]);
    }

    public static function encryptPassword(string $plain): string {
        $key = base64_decode(getenv('APP_KEY'));
        $iv  = substr(hash('sha256', getenv('APP_KEY'), true), 0, 16);
        return base64_encode(openssl_encrypt($plain, 'AES-256-CBC', $key, 0, $iv));
    }

    public static function decryptPassword(string $encrypted): string {
        $key = base64_decode(getenv('APP_KEY'));
        $iv  = substr(hash('sha256', getenv('APP_KEY'), true), 0, 16);
        return openssl_decrypt(base64_decode($encrypted), 'AES-256-CBC', $key, 0, $iv);
    }
}
