<?php
declare(strict_types=1);

abstract class Model
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    protected function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    protected function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    protected function execute(string $sql, array $params = []): int
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    protected function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $placeholders = array_map(static fn ($c) => ':' . $c, $cols);
        $sql = 'INSERT INTO ' . $table
             . ' (' . implode(', ', $cols) . ')'
             . ' VALUES (' . implode(', ', $placeholders) . ')';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        return (int)$this->db->lastInsertId();
    }

    protected function updateRow(string $table, int $id, array $data): int
    {
        $set = [];
        foreach (array_keys($data) as $col) {
            $set[] = $col . ' = :' . $col;
        }
        $data['id'] = $id;
        return $this->execute(
            'UPDATE ' . $table . ' SET ' . implode(', ', $set) . ' WHERE id = :id',
            $data
        );
    }

    protected function deleteRow(string $table, int $id): int
    {
        return $this->execute('DELETE FROM ' . $table . ' WHERE id = ?', [$id]);
    }

    protected function fetchScalar(string $sql, array $params = []): mixed
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $value = $stmt->fetchColumn();
        return $value === false ? 0 : $value;
    }

    protected function fetchInt(string $sql, array $params = []): int
    {
        return (int)$this->fetchScalar($sql, $params);
    }
}
