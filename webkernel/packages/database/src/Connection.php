<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Database;

use DateTimeInterface;
use Generator;
use PDO;
use Throwable;
use Webkernel\Database\Query\Builder;
use Webkernel\Database\Query\Expression;
use Webkernel\Database\Query\Grammars\Grammar as QueryGrammar;
use Webkernel\Database\Query\Grammars\MySqlGrammar;
use Webkernel\Database\Query\Grammars\PostgresGrammar;
use Webkernel\Database\Query\Grammars\SQLiteGrammar;
use Webkernel\Database\Query\Grammars\SqlServerGrammar;
use Webkernel\Database\Query\Processors\Processor;

/** PDO connection adapter used by Eloquent and the SQL query builder. */
final class Connection implements ConnectionInterface
{
    private readonly QueryGrammar $grammar;

    private readonly Processor $processor;

    private int $transaction_level = 0;

    public function __construct(
        private readonly PDO $pdo,
        private readonly ?string $name = null,
        private readonly ?string $database_name = null,
    ) {
        $this->grammar = match ((string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME)) {
            'mysql', 'mariadb' => new MySqlGrammar($pdo),
            'pgsql' => new PostgresGrammar($pdo),
            'sqlite' => new SQLiteGrammar($pdo),
            'sqlsrv', 'dblib', 'mssql' => new SqlServerGrammar($pdo),
            default => new QueryGrammar($pdo),
        };
        $this->processor = new Processor();
    }

    public function getQueryGrammar(): QueryGrammar
    {
        return $this->grammar;
    }

    public function getPostProcessor(): Processor
    {
        return $this->processor;
    }

    public function query(): Builder
    {
        return new Builder($this);
    }

    public function select(string $query, array $bindings = []): array
    {
        $statement = $this->pdo->prepare($query);
        $statement->execute($this->prepareBindings($bindings));

        return $statement->fetchAll(PDO::FETCH_OBJ);
    }

    public function cursor(string $query, array $bindings = []): Generator
    {
        $statement = $this->pdo->prepare($query);
        $statement->execute($this->prepareBindings($bindings));
        while (($row = $statement->fetch(PDO::FETCH_OBJ)) !== false) {
            yield $row;
        }
    }

    public function insert(string $query, array $bindings = []): bool
    {
        $this->statement($query, $bindings);

        return true;
    }

    public function update(string $query, array $bindings = []): int
    {
        return $this->affectingStatement($query, $bindings);
    }

    public function delete(string $query, array $bindings = []): int
    {
        return $this->affectingStatement($query, $bindings);
    }

    public function affectingStatement(string $query, array $bindings = []): int
    {
        $statement = $this->pdo->prepare($query);
        $statement->execute($this->prepareBindings($bindings));

        return $statement->rowCount();
    }

    public function statement(string $query, array $bindings = []): bool
    {
        $statement = $this->pdo->prepare($query);
        return $statement->execute($this->prepareBindings($bindings));
    }

    public function raw(mixed $value): Expression
    {
        return new Expression($value);
    }

    public function selectFromWriteConnection(string $query, array $bindings = []): array
    {
        return $this->select($query, $bindings);
    }

    public function recordsHaveBeenModified(bool $modified = true): void
    {
    }

    public function prepareBindings(array $bindings): array
    {
        foreach ($bindings as $key => $value) {
            if ($value instanceof DateTimeInterface) {
                $bindings[$key] = $value->format('Y-m-d H:i:s.u');
            } elseif (is_bool($value)) {
                $bindings[$key] = (int) $value;
            }
        }

        return $bindings;
    }

    public function getSchemaBuilder(): SchemaBuilder
    {
        return new SchemaBuilder($this);
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function getReadPdo(): PDO
    {
        return $this->pdo;
    }

    public function getDriverName(): string
    {
        return (string) $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    public function getDatabaseName(): ?string
    {
        return $this->database_name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function transactionLevel(): int
    {
        return $this->transaction_level;
    }

    public function transaction(callable $callback): mixed
    {
        $savepoint = 'webkernel_'.$this->transaction_level;
        if ($this->transaction_level === 0) {
            $this->pdo->beginTransaction();
        } else {
            $this->pdo->exec('SAVEPOINT '.$savepoint);
        }
        $this->transaction_level++;

        try {
            $result = $callback($this);
            $this->transaction_level--;
            if ($this->transaction_level === 0) {
                $this->pdo->commit();
            } else {
                $this->pdo->exec('RELEASE SAVEPOINT '.$savepoint);
            }

            return $result;
        } catch (Throwable $exception) {
            $this->transaction_level--;
            if ($this->transaction_level === 0 && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            } elseif ($this->transaction_level >= 0) {
                $this->pdo->exec('ROLLBACK TO SAVEPOINT '.$savepoint);
            }

            throw $exception;
        }
    }
}
