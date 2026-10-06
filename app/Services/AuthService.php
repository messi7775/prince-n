<?php
declare(strict_types=1);

namespace Services;

use Models\Admin;
use Session;

/**
 * AuthService — authentication business logic.
 *
 * Validates credentials against the admins table using password_verify,
 * and starts the admin session on success (README §7).
 */
final class AuthService
{
    private Admin $admin;

    public function __construct()
    {
        $this->admin = new Admin();
    }

    /** Attempt login; returns true on success, false on bad credentials. */
    public function attempt(string $email, string $password): bool
    {
        $record = $this->admin->findByEmail($email);

        if ($record === null) {
            return false;
        }

        if (!password_verify($password, $record['password_hash'])) {
            return false;
        }

        Session::login((int)$record['id'], $record['email']);
        return true;
    }
}
