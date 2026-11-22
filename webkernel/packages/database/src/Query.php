<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Database;

use InvalidArgumentException;
use PDO;

/** Small prepared query builder for model-owned SQL tables. */
final class Query
{
    /** @var list<string> */
    private array $conditions = [];

    /** @var array<string, scalar|null> */
    private array $bindings = [];

    private ?int $row_limit = null;

    private int $row_offset = 0;

    /** @param PDO $connection */
    public function __construct(private readonly PDO $connection, private readonly string $table)
    {
        self::assertIdentifier($table);
    }

    public function where(string $column, mixed $operator, mixed $value = null): self
    {
        self::assertIdentifier($column);
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        if (! is_string($operator) || ! in_array(strtolower($operator), ['=', '!=', '<>', '>', '>=', '<', '<=', 'like'], true)) {
            throw new InvalidArgumentException('The query operator is invalid.');
        }
        if ($value !== null && ! is_scalar($value)) {
            throw new InvalidArgumentException('Query values must be scalar or null.');
        }
        if ($value === null) {
            if (! in_array($operator, ['=', '!=', '<>'], true)) {
                throw new InvalidArgumentException('Null values support only equality comparisons.');
            }
            $this->conditions[] = $this->quote($column).(strtolower($operator) === '=' ? ' IS NULL' : ' IS NOT NULL');
            return $this;
        }

        $parameter = 'where_'.count($this->bindings);
        $this->conditions[] = $this->quote($column).' '.strtoupper($operator).' :'.$parameter;
        $this->bindings[$parameter] = $value;

        return $this;
    }

    public function limit(int $limit): self
    {
        if ($limit < 1 || $limit > 1000) {
            throw new InvalidArgumentException('Query limits must be between 1 and 1000.');
        }
        $this->row_limit = $limit;

        return $this;
    }

    public function offset(int $offset): self
    {
        if ($offset < 0) {
            throw new InvalidArgumentException('Query offsets cannot be negative.');
        }
        $this->row_offset = $offset;

        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        self::assertIdentifier($column);
        $direction = strtolower($direction);
        if (! in_array($direction, ['asc', 'desc'], true)) {
            throw new InvalidArgumentException('The query sort direction is invalid.');
        }
        $this->order = ' ORDER BY '.$this->quote($column).' '.strtoupper($direction);

        return $this;
    }

    /** @return list<array<string, mixed>> */
    public function get(): array
    {
        $statement = $this->connection->prepare($this->selectSql());
        $statement->execute($this->bindings);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function count(): int
    {
        $statement = $this->connection->prepare(
            'SELECT COUNT(*) FROM '.$this->quote($this->table).$this->whereSql(),
        );
        $statement->execute($this->bindings);

        return (int) $statement->fetchColumn();
    }

    /** @return array<string, mixed>|null */
    public function first(): ?array
    {
        $query = clone $this;
        $query->row_limit = 1;
        return $query->get()[0] ?? null;
    }

    /** @param array<string, mixed> $values */
    public function insert(array $values): int
    {
        if ($values === []) {
            throw new InvalidArgumentException('An insert requires at least one value.');
        }
        $columns = array_keys($values);
        foreach ($columns as $column) {
            if (! is_string($column)) {
                throw new InvalidArgumentException('Insert columns must be strings.');
            }
            self::assertIdentifier($column);
        }
        $sql = 'INSERT INTO '.$this->quote($this->table).' ('.implode(', ', array_map($this->quote(...), $columns)).')'
            .' VALUES ('.implode(', ', array_map(static fn (string $column): string => ':'.$column, $columns)).')';
        $this->connection->prepare($sql)->execute($values);
        return (int) $this->connection->lastInsertId();
    }

    /** @param array<string, mixed> $values */
    public function update(array $values): int
    {
        if ($values === [] || $this->conditions === []) {
            throw new InvalidArgumentException('Updates require values and at least one condition.');
        }
        $sets = [];
        $parameters = [];
        foreach ($values as $column => $value) {
            if (! is_string($column) || ! is_scalar($value) && $value !== null) {
                throw new InvalidArgumentException('Update values must use valid columns and scalar values.');
            }
            self::assertIdentifier($column);
            $parameter = 'set_'.$column;
            $sets[] = $this->quote($column).' = :'.$parameter;
            $parameters[$parameter] = $value;
        }
        $statement = $this->connection->prepare('UPDATE '.$this->quote($this->table).' SET '.implode(', ', $sets).$this->whereSql());
        $statement->execute([...$parameters, ...$this->bindings]);
        return $statement->rowCount();
    }

    public function delete(): int
    {
        if ($this->conditions === []) {
            throw new InvalidArgumentException('Deletes require at least one condition.');
        }
        $statement = $this->connection->prepare('DELETE FROM '.$this->quote($this->table).$this->whereSql());
        $statement->execute($this->bindings);
        return $statement->rowCount();
    }

    private string $order = '';

    private function selectSql(): string
    {
        return 'SELECT * FROM '.$this->quote($this->table).$this->whereSql().$this->order
            .($this->row_limit === null ? '' : ' LIMIT '.$this->row_limit)
            .($this->row_offset === 0 ? '' : ' OFFSET '.$this->row_offset);
    }

    private function whereSql(): string
    {
        return $this->conditions === [] ? '' : ' WHERE '.implode(' AND ', $this->conditions);
    }

    private function quote(string $identifier): string
    {
        $quote = $this->connection->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? '`' : '"';
        return $quote.$identifier.$quote;
    }

    private static function assertIdentifier(string $identifier): void
    {
        if (preg_match('/\A[a-zA-Z_][a-zA-Z0-9_]*\z/', $identifier) !== 1) {
            throw new InvalidArgumentException('SQL identifiers must be simple names.');
        }
    }
}
