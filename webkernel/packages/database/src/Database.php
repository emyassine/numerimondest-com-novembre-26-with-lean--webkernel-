<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Database;

use PDO;

/** Static entry point retained for concise model and query use. */
final class Database
{
    private static ?DatabaseManager $manager = null;

    /** @var array<string, PDO> */
    private static array $connections = [];

    private static ?string $default = null;

    public static function connection(?string $name = null): PDO
    {
        $name ??= self::$default;
        if ($name !== null && isset(self::$connections[$name])) {
            return self::$connections[$name];
        }
        return (self::$manager ??= new DatabaseManager())->connection($name);
    }

    public static function use(PDO $connection, ?string $name = null): PDO
    {
        $name ??= 'default';
        self::$connections[$name] = $connection;
        self::$default = $name;
        return $connection;
    }

    /** @param array<string, mixed> $configuration */
    public static function configure(array $configuration): void
    {
        self::$manager = new DatabaseManager($configuration);
        self::$connections = [];
        self::$default = null;
    }

    public static function table(string $table): Query
    {
        return new Query(self::connection(), $table);
    }

    public static function flush(): void
    {
        self::$manager = null;
        self::$connections = [];
        self::$default = null;
    }
}
