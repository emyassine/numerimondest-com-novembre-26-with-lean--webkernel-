<?php declare(strict_types=1);

namespace App\Studio\Persistence;

use PDO;
use RuntimeException;

/** SQLite persistence for draft and published catalog snapshots. */
final class OfferingStore
{
    private PDO $pdo;

    public function __construct(?string $path = null)
    {
        $path ??= base_path('storage/databases/offerings.sqlite');
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new RuntimeException('Cannot create offerings database directory.');
        }
        $this->pdo = new PDO('sqlite:'.$path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $this->pdo->exec('PRAGMA foreign_keys = ON');
        $this->pdo->exec('PRAGMA journal_mode = WAL');
        $this->migrate();
    }

    private function migrate(): void
    {
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS offering_revisions (
            revision INTEGER PRIMARY KEY AUTOINCREMENT,
            status TEXT NOT NULL CHECK(status IN (\'draft\', \'published\')),
            catalogue_json TEXT NOT NULL,
            created_at TEXT NOT NULL,
            created_by TEXT NOT NULL DEFAULT \'system\'
        )');
        $this->pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS one_published_offerings ON offering_revisions(status) WHERE status = \'published\'');
    }

    /** @return array{revision:int,catalogue:array}|null */
    public function current(string $status = 'draft'): ?array
    {
        if (!in_array($status, ['draft', 'published'], true)) throw new RuntimeException('Invalid revision status.');
        $statement = $this->pdo->prepare('SELECT revision, catalogue_json FROM offering_revisions WHERE status = :status ORDER BY revision DESC LIMIT 1');
        $statement->execute(['status' => $status]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        return ['revision' => (int) $row['revision'], 'catalogue' => json_decode($row['catalogue_json'], true, 512, JSON_THROW_ON_ERROR)];
    }

    /** @param array<string,mixed> $catalogue */
    public function saveDraft(array $catalogue, string $actor): int
    {
        $json = json_encode($catalogue, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->pdo->beginTransaction();
        try {
            $this->pdo->exec("DELETE FROM offering_revisions WHERE status = 'draft'");
            $statement = $this->pdo->prepare("INSERT INTO offering_revisions(status,catalogue_json,created_at,created_by) VALUES('draft',:json,:time,:actor)");
            $statement->execute(['json' => $json, 'time' => gmdate(DATE_ATOM), 'actor' => $actor]);
            $revision = (int) $this->pdo->lastInsertId();
            $this->pdo->commit();
            return $revision;
        } catch (\Throwable $error) { $this->pdo->rollBack(); throw $error; }
    }

    public function publish(string $actor): int
    {
        $draft = $this->current('draft');
        if ($draft === null) throw new RuntimeException('There is no draft catalog to publish.');
        $this->pdo->beginTransaction();
        try {
            $this->pdo->exec("DELETE FROM offering_revisions WHERE status = 'published'");
            $statement = $this->pdo->prepare("INSERT INTO offering_revisions(status,catalogue_json,created_at,created_by) VALUES('published',:json,:time,:actor)");
            $statement->execute(['json' => json_encode($draft['catalogue'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), 'time' => gmdate(DATE_ATOM), 'actor' => $actor]);
            $revision = (int) $this->pdo->lastInsertId();
            $this->pdo->commit();
            return $revision;
        } catch (\Throwable $error) { $this->pdo->rollBack(); throw $error; }
    }
}
