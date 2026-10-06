<?php
declare(strict_types=1);

namespace Models;

use Model;

final class Admin extends Model
{
    public function findByEmail(string $email): ?array
    {
        return $this->fetchOne(
            'SELECT id, email, password_hash FROM admins WHERE email = ? LIMIT 1',
            [$email]
        );
    }

    public function findById(int $id): ?array
    {
        return $this->fetchOne('SELECT id, email, password_hash FROM admins WHERE id = ? LIMIT 1', [$id]);
    }

    public function updatePassword(int $id, string $hash): int
    {
        return $this->execute(
            'UPDATE admins SET password_hash = ? WHERE id = ?',
            [$hash, $id]
        );
    }
}
