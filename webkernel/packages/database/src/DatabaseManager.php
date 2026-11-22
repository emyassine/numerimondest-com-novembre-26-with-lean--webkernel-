<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Database;

use InvalidArgumentException;
use PDO;
use PDOException;
use RuntimeException;
use Webkernel\Config\Config;

/** Creates and caches configured PDO connections for the current application process. */
final class DatabaseManager
{
    /** @var array<string, PDO> */
    private array $connections = [];

    /** @var array<string, ClickHouseConnection> */
    private array $clickhouse_connections = [];

    /** @param array<string, mixed>|null $configuration */
    public function __construct(private readonly ?array $configuration = null)
    {
    }

    /**
     * Returns a named PDO connection, or the configured default connection.
     *
     * @param $name string|null
     * @return PDO
     * @throws InvalidArgumentException When the connection declaration is invalid.
     * @throws RuntimeException When the PDO driver is unavailable or the connection fails.
     */
    public function connection(?string $name = null): PDO
    {
        $configuration = $this->configuration ?? Config::get('database', []);
        if (! is_array($configuration)) {
            throw new InvalidArgumentException('Database configuration must be an array.');
        }

        $name ??= $configuration['default'] ?? null;
        $connections = $configuration['connections'] ?? null;
        if (
            ! is_string($name)
            || preg_match('/\A[a-zA-Z][a-zA-Z0-9_-]{0,63}\z/', $name) !== 1
            || ! is_array($connections)
            || ! is_array($connections[$name] ?? null)
        ) {
            throw new InvalidArgumentException('The requested database connection is not configured.');
        }

        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }

        $connection = $connections[$name];
        $driver = $connection['driver'] ?? null;
        if ($driver === 'clickhouse') {
            throw new RuntimeException('ClickHouse is an analytics connection; use clickhouse() instead of PDO.');
        }
        if (! is_string($driver) || ! in_array($driver, ['sqlite', 'mysql', 'pgsql'], true)) {
            throw new InvalidArgumentException('The configured database driver is unsupported.');
        }
        if (! in_array($driver, PDO::getAvailableDrivers(), true)) {
            throw new RuntimeException('The configured PDO driver is unavailable.');
        }

        $dsn = match ($driver) {
            'sqlite' => $this->sqliteDsn($connection),
            'mysql' => $this->mysqlDsn($connection),
            'pgsql' => $this->pgsqlDsn($connection),
        };
        $username = $driver === 'sqlite' ? null : $this->optionalString($connection, 'username');
        $password = $driver === 'sqlite' ? null : $this->optionalString($connection, 'password');

        try {
            return $this->connections[$name] = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $exception) {
            throw new RuntimeException('The configured database connection could not be opened.', previous: $exception);
        }
    }

    /** Returns a configured ClickHouse HTTP client by connection name. */
    public function clickhouse(?string $name = null): ClickHouseConnection
    {
        $configuration = $this->configuration ?? Config::get('database', []);
        $name ??= is_array($configuration) ? ($configuration['default'] ?? null) : null;
        $connections = is_array($configuration) ? ($configuration['connections'] ?? null) : null;
        if (! is_string($name) || ! is_array($connections) || ! is_array($connections[$name] ?? null)) {
            throw new InvalidArgumentException('The requested database connection is not configured.');
        }
        if (($connections[$name]['driver'] ?? null) !== 'clickhouse') {
            throw new InvalidArgumentException('The selected connection is not configured for ClickHouse.');
        }

        return $this->clickhouse_connections[$name] ??= new ClickHouseConnection($connections[$name]);
    }

    public function table(string $table): Query
    {
        return new Query($this->connection(), $table);
    }

    /** @param array<string, mixed> $connection */
    private function sqliteDsn(array $connection): string
    {
        $database = $connection['database'] ?? null;
        if (! is_string($database) || $database === '' || str_contains($database, "\0")) {
            throw new InvalidArgumentException('The SQLite database path is invalid.');
        }
        if ($database === ':memory:') {
            return 'sqlite::memory:';
        }
        if (
            str_starts_with($database, '/')
            || preg_match('~(?:\A|/)\.\.(?:/|\z)|\A[A-Za-z]:~', $database) === 1
        ) {
            throw new InvalidArgumentException('SQLite database paths must remain within the application root.');
        }

        $path = base_path($database);
        $directory = dirname($path);
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException('The SQLite database directory could not be created.');
        }

        return 'sqlite:'.$path;
    }

    /** @param array<string, mixed> $connection */
    private function mysqlDsn(array $connection): string
    {
        $host = $this->requiredString($connection, 'host');
        $database = $this->requiredString($connection, 'database');
        $port = $this->port($connection['port'] ?? 3306);
        $charset = $connection['charset'] ?? 'utf8mb4';
        if (! is_string($charset) || preg_match('/\A[a-zA-Z0-9_]+\z/', $charset) !== 1) {
            throw new InvalidArgumentException('The MySQL character set is invalid.');
        }

        return 'mysql:host='.$host.';port='.$port.';dbname='.$database.';charset='.$charset;
    }

    /** @param array<string, mixed> $connection */
    private function pgsqlDsn(array $connection): string
    {
        return 'pgsql:host='.$this->requiredString($connection, 'host')
            .';port='.$this->port($connection['port'] ?? 5432)
            .';dbname='.$this->requiredString($connection, 'database');
    }

    /** @param array<string, mixed> $connection */
    private function requiredString(array $connection, string $key): string
    {
        $value = $connection[$key] ?? null;
        if (! is_string($value) || $value === '' || str_contains($value, "\0") || str_contains($value, ';')) {
            throw new InvalidArgumentException('The configured database '.$key.' is invalid.');
        }

        return $value;
    }

    /** @param array<string, mixed> $connection */
    private function optionalString(array $connection, string $key): ?string
    {
        $value = $connection[$key] ?? null;
        if ($value !== null && ! is_string($value)) {
            throw new InvalidArgumentException('The configured database '.$key.' is invalid.');
        }

        return $value;
    }

    private function port(mixed $port): int
    {
        if (! is_int($port) || $port < 1 || $port > 65535) {
            throw new InvalidArgumentException('The configured database port is invalid.');
        }

        return $port;
    }
}
