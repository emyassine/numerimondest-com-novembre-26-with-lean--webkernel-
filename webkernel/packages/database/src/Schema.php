<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Database;

use InvalidArgumentException;
use PDO;

/** Creates and removes SQL tables through explicit migration callbacks. */
final class Schema
{
    /** @param callable(Blueprint): void $define */
    public static function create(string $table, callable $define): void
    {
        $connection = Database::connection();
        $blueprint = new Blueprint($connection, $table);
        $define($blueprint);
        $connection->exec($blueprint->toSql());
    }

    public static function dropIfExists(string $table): void
    {
        self::drop($table);
    }

    public static function drop(string $table): void
    {
        self::assertIdentifier($table);
        $connection = Database::connection();
        $quote = $connection->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? '`' : '"';
        $connection->exec('DROP TABLE IF EXISTS '.$quote.$table.$quote);
    }

    public static function hasTable(string $table): bool
    {
        self::assertIdentifier($table);
        $connection = Database::connection();
        $quote = $connection->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? '`' : '"';
        try {
            $connection->query('SELECT 1 FROM '.$quote.$table.$quote.' LIMIT 1');
            return true;
        } catch (\PDOException) {
            return false;
        }
    }

    private static function assertIdentifier(string $identifier): void
    {
        if (preg_match('/\A[a-zA-Z_][a-zA-Z0-9_]*\z/', $identifier) !== 1) {
            throw new InvalidArgumentException('SQL identifiers must be simple names.');
        }
    }
}
