<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Database;

use InvalidArgumentException;
use PDO;

/** Reads table metadata for generators and database tooling. */
final class SchemaBuilder
{
    public function __construct(private readonly Connection $connection) {}

    /** @return list<string> */
    public function getColumnListing(string $table): array
    {
        return array_column($this->getColumns($table), 'name');
    }

    /** @return list<array{name:string,type:string,type_name:string,nullable:bool,default:mixed}> */
    public function getColumns(string $table): array
    {
        self::assertIdentifier($table);
        $pdo = $this->connection->getPdo();
        $driver = (string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $rows = match ($driver) {
            'sqlite' => $pdo->query('PRAGMA table_info("'.$table.'")')->fetchAll(PDO::FETCH_ASSOC),
            'mysql' => $pdo->query('SHOW FULL COLUMNS FROM `'.$table.'`')->fetchAll(PDO::FETCH_ASSOC),
            'pgsql' => $this->connection->select('SELECT column_name, data_type, is_nullable, column_default FROM information_schema.columns WHERE table_name = ? ORDER BY ordinal_position', [$table]),
            default => throw new InvalidArgumentException("Schema introspection is not supported for [{$driver}]."),
        };
        $columns = [];
        foreach ($rows as $row) {
            $name = (string) ($row['name'] ?? $row['Field'] ?? $row->column_name ?? '');
            $type = (string) ($row['type'] ?? $row['Type'] ?? $row->data_type ?? '');
            $nullable = (string) ($row['notnull'] ?? '') !== '1' && strtoupper((string) ($row['Null'] ?? $row->is_nullable ?? 'YES')) !== 'NO';
            $columns[] = ['name' => $name, 'type' => strtolower($type), 'type_name' => strtolower((string) preg_replace('/\\(.*/', '', $type)), 'nullable' => $nullable, 'default' => $row['dflt_value'] ?? $row['Default'] ?? $row->column_default ?? null];
        }
        return $columns;
    }

    /** @return list<array{name:string,columns:list<string>,primary:bool,unique:bool,type:string}> */
    public function getIndexes(string $table): array
    {
        self::assertIdentifier($table);
        $pdo = $this->connection->getPdo();
        $driver = (string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $indexes = [];
            foreach ($pdo->query('PRAGMA index_list("'.$table.'")')->fetchAll(PDO::FETCH_ASSOC) as $index) {
                $columns = array_column($pdo->query('PRAGMA index_info("'.$index['name'].'")')->fetchAll(PDO::FETCH_ASSOC), 'name');
                $indexes[] = ['name' => (string) $index['name'], 'columns' => array_values(array_filter($columns, 'is_string')), 'primary' => str_starts_with((string) $index['name'], 'sqlite_autoindex'), 'unique' => (bool) $index['unique'], 'type' => 'btree'];
            }
            return $indexes;
        }
        if ($driver === 'mysql') {
            $rows = $pdo->query('SHOW INDEX FROM `'.$table.'`')->fetchAll(PDO::FETCH_ASSOC);
            $indexes = [];
            foreach ($rows as $row) {
                $name = (string) $row['Key_name'];
                $indexes[$name] ??= ['name' => $name, 'columns' => [], 'primary' => $name === 'PRIMARY', 'unique' => (int) $row['Non_unique'] === 0, 'type' => strtolower((string) $row['Index_type'])];
                $indexes[$name]['columns'][(int) $row['Seq_in_index']] = (string) $row['Column_name'];
            }
            foreach ($indexes as &$index) { $index['columns'] = array_values($index['columns']); }
            return array_values($indexes);
        }
        if ($driver === 'pgsql') {
            $rows = $this->connection->select('SELECT indexname, indexdef FROM pg_indexes WHERE tablename = ?', [$table]);
            $indexes = [];
            foreach ($rows as $row) {
                preg_match('/\\(([^)]+)\\)/', $row->indexdef, $match);
                $columns = isset($match[1]) ? array_map(static fn (string $column): string => trim($column, ' "'), explode(',', $match[1])) : [];
                $indexes[] = ['name' => $row->indexname, 'columns' => $columns, 'primary' => str_contains(strtolower($row->indexdef), 'primary key'), 'unique' => str_contains(strtolower($row->indexdef), 'unique'), 'type' => 'btree'];
            }
            return $indexes;
        }
        throw new InvalidArgumentException("Schema introspection is not supported for [{$driver}].");
    }

    private static function assertIdentifier(string $name): void
    {
        if (preg_match('/\\A[a-zA-Z_][a-zA-Z0-9_]*\\z/', $name) !== 1) { throw new InvalidArgumentException('Invalid SQL identifier.'); }
    }
}
