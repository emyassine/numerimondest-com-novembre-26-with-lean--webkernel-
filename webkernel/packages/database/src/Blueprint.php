<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Database;

use InvalidArgumentException;
use PDO;

/** Defines columns and unique constraints for a new SQL table. */
final class Blueprint
{
    /** @var list<string> */
    private array $definitions = [];

    private ?string $pending_foreign_id = null;

    /** @param PDO $connection */
    public function __construct(private readonly PDO $connection, private readonly string $table)
    {
        self::assertIdentifier($table);
    }

    public function increments(string $name = 'id'): self
    {
        $column = $this->quote($name);
        $this->definitions[] = match ($this->driver()) {
            'sqlite' => $column.' INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL',
            'pgsql' => $column.' SERIAL PRIMARY KEY',
            'mysql' => $column.' BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            default => throw new InvalidArgumentException('This SQL driver does not support schema migrations.'),
        };
        return $this;
    }

    public function id(string $name = 'id'): self { return $this->increments($name); }

    public function string(string $name, int $length = 255): self
    {
        if ($length < 1 || $length > 65535) {
            throw new InvalidArgumentException('String lengths must be between 1 and 65535.');
        }
        $type = $this->driver() === 'sqlite' ? 'TEXT' : 'VARCHAR('.$length.')';
        $this->definitions[] = $this->quote($name).' '.$type.' NOT NULL';
        return $this;
    }

    public function text(string $name): self
    {
        $this->definitions[] = $this->quote($name).' TEXT NOT NULL';
        return $this;
    }

    public function json(string $name): self
    {
        $type = $this->driver() === 'mysql' ? 'JSON' : 'TEXT';
        $this->definitions[] = $this->quote($name).' '.$type.' NOT NULL';
        return $this;
    }

    public function timestamp(string $name): self
    {
        $type = $this->driver() === 'sqlite' ? 'TEXT' : 'TIMESTAMP';
        $this->definitions[] = $this->quote($name).' '.$type.' NOT NULL';
        return $this;
    }

    public function unsignedInteger(string $name): self
    {
        $type = $this->driver() === 'mysql' ? 'INT UNSIGNED' : 'INTEGER';
        $this->definitions[] = $this->quote($name).' '.$type.' NOT NULL';
        return $this;
    }

    public function nullable(): self
    {
        $index = array_key_last($this->definitions);
        if ($index !== null) { $this->definitions[$index] = preg_replace('/\\s+NOT NULL\\z/', ' NULL', $this->definitions[$index]) ?? $this->definitions[$index]; }
        return $this;
    }

    public function default(int|float|string|bool|null $value): self
    {
        $index = array_key_last($this->definitions);
        if ($index === null) { return $this; }
        if (is_string($value)) { $encoded = $this->connection->quote($value); }
        elseif (is_bool($value)) { $encoded = $value ? '1' : '0'; }
        elseif ($value === null) { $encoded = 'NULL'; }
        else { $encoded = (string) $value; }
        $this->definitions[$index] .= ' DEFAULT '.$encoded;
        return $this;
    }

    public function integer(string $name, ?int $default = null): self
    {
        $sql = $this->quote($name).($this->driver() === 'mysql' ? ' INT NOT NULL' : ' INTEGER NOT NULL');
        if ($default !== null) { $sql .= ' DEFAULT '.$default; }
        $this->definitions[] = $sql;
        return $this;
    }

    public function boolean(string $name, bool $default = false): self
    {
        $type = match ($this->driver()) { 'pgsql' => 'BOOLEAN', default => 'INTEGER' };
        $value = $this->driver() === 'pgsql' ? ($default ? 'TRUE' : 'FALSE') : ($default ? '1' : '0');
        $this->definitions[] = $this->quote($name).' '.$type.' NOT NULL DEFAULT '.$value;
        return $this;
    }

    public function foreignId(string $name): self
    {
        $type = $this->driver() === 'mysql' ? 'BIGINT UNSIGNED' : 'INTEGER';
        $this->definitions[] = $this->quote($name).' '.$type.' NOT NULL';
        $this->pending_foreign_id = $name;
        return $this;
    }

    public function constrained(?string $table = null, string $column = 'id'): self
    {
        if ($this->pending_foreign_id === null) { throw new InvalidArgumentException('constrained() must follow foreignId().'); }
        $name = $this->pending_foreign_id;
        $table ??= preg_replace('/_id$/', '', $name).'s';
        $this->definitions[] = 'FOREIGN KEY ('.$this->quote($name).') REFERENCES '.$this->quote($table).' ('.$this->quote($column).')';
        $this->pending_foreign_id = null;
        return $this;
    }

    public function cascadeOnDelete(): self
    {
        $index = array_key_last($this->definitions);
        if ($index !== null && str_starts_with($this->definitions[$index], 'FOREIGN KEY ')) { $this->definitions[$index] .= ' ON DELETE CASCADE'; }
        return $this;
    }

    /** @param string|list<string> $columns */
    public function primary(string|array $columns): self
    {
        $columns = is_array($columns) ? $columns : [$columns];
        $this->definitions[] = 'PRIMARY KEY ('.implode(', ', array_map($this->quote(...), $columns)).')';
        return $this;
    }

    public function timestamps(): self
    {
        $type = $this->driver() === 'sqlite' ? 'TEXT' : 'TIMESTAMP';
        $this->definitions[] = $this->quote('created_at').' '.$type.' NULL';
        $this->definitions[] = $this->quote('updated_at').' '.$type.' NULL';
        return $this;
    }

    /** @param string|list<string> $column */
    public function unique(string|array $column): self
    {
        $columns = is_array($column) ? $column : [$column];
        $this->definitions[] = 'UNIQUE ('.implode(', ', array_map($this->quote(...), $columns)).')';
        return $this;
    }

    public function toSql(): string
    {
        if ($this->definitions === []) {
            throw new InvalidArgumentException('A table requires at least one column.');
        }
        // SQLite requires table constraints to follow every column definition.
        $columns = [];
        $constraints = [];
        foreach ($this->definitions as $definition) {
            if (str_starts_with($definition, 'FOREIGN KEY ') || str_starts_with($definition, 'PRIMARY KEY ')) {
                $constraints[] = $definition;
            } else {
                $columns[] = $definition;
            }
        }
        return 'CREATE TABLE '.$this->quote($this->table).' ('.implode(', ', [...$columns, ...$constraints]).')';
    }

    private function quote(string $identifier): string
    {
        self::assertIdentifier($identifier);
        $quote = $this->driver() === 'mysql' ? '`' : '"';
        return $quote.$identifier.$quote;
    }

    private function driver(): string
    {
        return (string) $this->connection->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    private static function assertIdentifier(string $identifier): void
    {
        if (preg_match('/\A[a-zA-Z_][a-zA-Z0-9_]*\z/', $identifier) !== 1) {
            throw new InvalidArgumentException('SQL identifiers must be simple names.');
        }
    }
}
