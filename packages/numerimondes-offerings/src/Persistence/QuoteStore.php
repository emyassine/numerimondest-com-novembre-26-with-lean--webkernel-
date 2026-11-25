<?php declare(strict_types=1);

namespace Numerimondes\Offerings\Persistence;

use Numerimondes\Offerings\Pricing\QuoteSnapshot;
use PDO;

/** Immutable quote snapshots stored beside the catalogue revision database. */
final class QuoteStore
{
    private PDO $pdo;

    public function __construct(?string $path = null)
    {
        $path ??= OfferingStore::defaultPath();
        if (!is_file($path)) throw new \RuntimeException('Offerings database is not initialized; run php scripts/migrate-offerings-database.php.');
        $this->pdo = new PDO('sqlite:'.$path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $this->pdo->exec('PRAGMA foreign_keys = ON');
        if (!$this->tableExists('quote_snapshots') || !$this->tableExists('quote_settlements')) {
            throw new \RuntimeException('Quote database schema is incomplete; run php scripts/migrate-offerings-database.php.');
        }
    }

    /** Creates immutable quote and settlement tables during deployment migration. */
    public static function initialize(?string $path = null): void
    {
        $path ??= OfferingStore::defaultPath();
        OfferingStore::initialize($path);
        $pdo = new PDO('sqlite:'.$path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('CREATE TABLE IF NOT EXISTS quote_snapshots (
            quote_id TEXT PRIMARY KEY,
            partner_id TEXT NOT NULL,
            catalog_revision TEXT NOT NULL,
            pricing_revision TEXT NOT NULL,
            currency TEXT NOT NULL CHECK(length(currency) = 3),
            lines_json TEXT NOT NULL,
            created_at TEXT NOT NULL,
            expires_at TEXT NOT NULL
        )');
        $pdo->exec('CREATE TABLE IF NOT EXISTS quote_settlements (
            quote_id TEXT PRIMARY KEY REFERENCES quote_snapshots(quote_id) ON DELETE CASCADE,
            settlement_json TEXT NOT NULL,
            created_at TEXT NOT NULL
        )');
    }

    private function tableExists(string $name): bool
    {
        $statement = $this->pdo->prepare("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = :name");
        $statement->execute(['name' => $name]);
        return $statement->fetchColumn() !== false;
    }

    /** @param array<string,mixed> $internalSettlement Never serialized to the quote API response. */
    public function save(QuoteSnapshot $quote, array $internalSettlement = []): void
    {
        $this->pdo->beginTransaction();
        try {
        $statement = $this->pdo->prepare('INSERT INTO quote_snapshots(quote_id,partner_id,catalog_revision,pricing_revision,currency,lines_json,created_at,expires_at) VALUES(:id,:partner,:catalog,:pricing,:currency,:lines,:created,:expires)');
        $statement->execute([
            'id' => $quote->quoteId,
            'partner' => $quote->partnerId,
            'catalog' => $quote->catalogRevision,
            'pricing' => $quote->pricingRevision,
            'currency' => $quote->currency,
            'lines' => json_encode($quote->lines, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created' => $quote->createdAt->format(DATE_ATOM),
            'expires' => $quote->expiresAt->format(DATE_ATOM),
        ]);
        if ($internalSettlement !== []) {
            $settlement = $this->pdo->prepare('INSERT INTO quote_settlements(quote_id,settlement_json,created_at) VALUES(:id,:payload,:created)');
            $settlement->execute(['id' => $quote->quoteId, 'payload' => json_encode($internalSettlement, JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES), 'created' => $quote->createdAt->format(DATE_ATOM)]);
        }
        $this->pdo->commit();
        } catch (\Throwable $error) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $error;
        }
    }

    public function find(string $quoteId): ?QuoteSnapshot
    {
        $statement = $this->pdo->prepare('SELECT * FROM quote_snapshots WHERE quote_id = :id LIMIT 1');
        $statement->execute(['id' => $quoteId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) return null;
        return new QuoteSnapshot(
            $row['quote_id'], $row['partner_id'], $row['catalog_revision'], $row['pricing_revision'], $row['currency'],
            json_decode($row['lines_json'], true, 512, JSON_THROW_ON_ERROR),
            new \DateTimeImmutable($row['created_at']), new \DateTimeImmutable($row['expires_at']),
        );
    }
}
