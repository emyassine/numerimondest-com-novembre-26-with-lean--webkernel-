<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Database;

use InvalidArgumentException;
use Webkernel\Config\Config;

/** Resolves configured database connections for ORM models. */
final class ConnectionResolver implements ConnectionResolverInterface
{
    /** @var array<string, Connection> */
    private array $connections = [];

    public function __construct(private ?string $default_connection = null)
    {
    }

    public function connection(?string $name = null): Connection
    {
        $name ??= $this->default_connection;
        if (! is_string($name) || $name === '') {
            throw new InvalidArgumentException('No default database connection is configured.');
        }

        $configuration = Config::get('database', []);
        $database_name = is_array($configuration)
            ? ($configuration['connections'][$name]['database'] ?? null)
            : null;

        return $this->connections[$name] ??= new Connection(
            Database::connection($name),
            $name,
            is_string($database_name) ? $database_name : null,
        );
    }

    public function getDefaultConnection(): ?string
    {
        return $this->default_connection;
    }

    public function setDefaultConnection(string $name): void
    {
        if ($name === '') {
            throw new InvalidArgumentException('The default database connection name cannot be empty.');
        }

        $this->default_connection = $name;
    }
}
