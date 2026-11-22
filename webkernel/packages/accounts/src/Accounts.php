<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Accounts;

use PDO;
use RuntimeException;
use Webkernel\Database\Database;

/** Persists shared account credentials and explicit permission assignments. */
final class Accounts
{
    public function authenticate(string $email, string $password): ?User
    {
        $statement = Database::connection()->prepare('SELECT id, email, password_hash, active FROM accounts WHERE email = :email');
        $statement->execute(['email' => mb_strtolower(trim($email))]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        $valid = is_array($row) && is_string($row['password_hash']) && password_verify($password, $row['password_hash']);
        if (! $valid || (int) $row['active'] !== 1) {
            return null;
        }
        if (password_needs_rehash($row['password_hash'], PASSWORD_DEFAULT)) {
            $update = Database::connection()->prepare('UPDATE accounts SET password_hash = :hash WHERE id = :id');
            $update->execute(['hash' => password_hash($password, PASSWORD_DEFAULT), 'id' => $row['id']]);
        }

        return $this->find((int) $row['id']);
    }

    public function find(int $id): ?User
    {
        $statement = Database::connection()->prepare('SELECT id, email, active FROM accounts WHERE id = :id');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if (! is_array($row) || (int) $row['active'] !== 1) {
            return null;
        }
        $permissions = Database::connection()->prepare('SELECT permission FROM account_permissions WHERE account_id = :id');
        $permissions->execute(['id' => $id]);
        return new User($id, (string) $row['email'], true, array_map('strval', $permissions->fetchAll(PDO::FETCH_COLUMN)));
    }

    /** Creates or promotes the initial administrator while no panel administrator exists. */
    public function createFirstAdministrator(string $email, string $password): int
    {
        $email = mb_strtolower(trim($email));
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || strlen($password) < 12) {
            throw new RuntimeException('Use a valid email and a password of at least 12 characters.');
        }
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $pdo->exec('UPDATE account_bootstrap SET lock_version = lock_version WHERE id = 1');
            $admin_check = $pdo->prepare('SELECT COUNT(*) FROM account_permissions WHERE permission = :permission');
            $admin_check->execute(['permission' => 'system_panel.access']);
            if ((int) $admin_check->fetchColumn() !== 0) {
                throw new RuntimeException('A System Panel administrator already exists; first administrator creation is closed.');
            }

            $find = $pdo->prepare('SELECT id, password_hash, active FROM accounts WHERE email = :email');
            $find->execute(['email' => $email]);
            $existing = $find->fetch(PDO::FETCH_ASSOC);
            if (is_array($existing)) {
                if ((int) $existing['active'] !== 1) {
                    throw new RuntimeException('The matching account is disabled. Enable it before granting administrator access.');
                }
                if (! is_string($existing['password_hash']) || ! password_verify($password, $existing['password_hash'])) {
                    throw new RuntimeException('The password does not match the existing account.');
                }
                $id = (int) $existing['id'];
            } else {
                $insert = $pdo->prepare('INSERT INTO accounts (email, password_hash, active) VALUES (:email, :hash, 1)');
                $insert->execute(['email' => $email, 'hash' => password_hash($password, PASSWORD_DEFAULT)]);
                $id = (int) $pdo->lastInsertId();
            }

            $grant = $pdo->prepare('INSERT INTO account_permissions (account_id, permission) VALUES (:id, :permission)');
            $grant->execute(['id' => $id, 'permission' => 'system_panel.access']);
            $pdo->commit();
            return $id;
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            throw $exception;
        }
    }

    /** Creates a regular account without granting any panel permission. */
    public function register(string $email, string $password): int
    {
        $email = mb_strtolower(trim($email));
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || strlen($password) < 12) {
            throw new RuntimeException('Use a valid email and a password of at least 12 characters.');
        }

        $statement = Database::connection()->prepare('INSERT INTO accounts (email, password_hash, active) VALUES (:email, :hash, 1)');
        $statement->execute(['email' => $email, 'hash' => password_hash($password, PASSWORD_DEFAULT)]);

        return (int) Database::connection()->lastInsertId();
    }
}
