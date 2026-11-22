<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Database;

use InvalidArgumentException;
use RuntimeException;

/** HTTP client for ClickHouse, whose analytics semantics differ from PDO SQL stores. */
final class ClickHouseConnection
{
    private readonly string $endpoint;
    private readonly string $database;
    private readonly ?string $username;
    private readonly ?string $password;
    private readonly int $timeout;

    /** @param array<string, mixed> $configuration */
    public function __construct(array $configuration)
    {
        $host = $configuration['host'] ?? '127.0.0.1';
        $port = $configuration['port'] ?? 8123;
        $secure = $configuration['secure'] ?? false;
        $database = $configuration['database'] ?? 'default';
        $this->username = $configuration['username'] ?? null;
        $this->password = $configuration['password'] ?? null;
        $this->timeout = $configuration['timeout'] ?? 5;

        if (! is_string($host) || preg_match('/\A[a-zA-Z0-9.-]+\z/', $host) !== 1) {
            throw new InvalidArgumentException('The ClickHouse host is invalid.');
        }
        if (! is_int($port) || $port < 1 || $port > 65535) {
            throw new InvalidArgumentException('The ClickHouse port is invalid.');
        }
        if (! is_bool($secure) || ! is_string($database) || preg_match('/\A[a-zA-Z0-9_]+\z/', $database) !== 1) {
            throw new InvalidArgumentException('The ClickHouse connection settings are invalid.');
        }
        if (
            ($this->username !== null && (! is_string($this->username) || preg_match('/[\r\n]/', $this->username) === 1))
            || ($this->password !== null && (! is_string($this->password) || preg_match('/[\r\n]/', $this->password) === 1))
        ) {
            throw new InvalidArgumentException('The ClickHouse credentials are invalid.');
        }
        if (! is_int($this->timeout) || $this->timeout < 1 || $this->timeout > 60) {
            throw new InvalidArgumentException('The ClickHouse timeout must be between 1 and 60 seconds.');
        }

        $this->database = $database;
        $this->endpoint = ($secure ? 'https' : 'http').'://'.$host.':'.$port.'/?database='.rawurlencode($database);
    }

    /** Executes a read query and returns ClickHouse JSONEachRow records. @return list<array<string, mixed>> */
    public function select(string $sql): array
    {
        $body = $this->send($this->appendFormat($sql, 'JSONEachRow'));
        if ($body === '') {
            return [];
        }

        $rows = [];
        foreach (preg_split('/\r?\n/', trim($body)) ?: [] as $line) {
            $row = json_decode($line, true, flags: JSON_THROW_ON_ERROR);
            if (! is_array($row)) {
                throw new RuntimeException('ClickHouse returned an invalid row.');
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /** Executes a DDL or write query and returns the response body. */
    public function execute(string $sql): string
    {
        return $this->send($sql);
    }

    private function send(string $sql): string
    {
        $headers = ['Content-Type: text/plain; charset=utf-8'];
        if ($this->username !== null) {
            $headers[] = 'X-ClickHouse-User: '.$this->username;
        }
        if ($this->password !== null) {
            $headers[] = 'X-ClickHouse-Key: '.$this->password;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $sql,
                'timeout' => $this->timeout,
                'ignore_errors' => true,
            ],
        ]);
        $body = @file_get_contents($this->endpoint, false, $context);
        $status = $http_response_header[0] ?? '';
        if ($body === false || preg_match('/\s2\d\d\s/', $status) !== 1) {
            throw new RuntimeException('The ClickHouse request failed with status '.($status ?: 'unavailable').'.');
        }

        return $body;
    }

    private function appendFormat(string $sql, string $format): string
    {
        $sql = rtrim($sql);
        if ($sql === '' || str_contains($sql, "\0") || str_ends_with($sql, ';')) {
            throw new InvalidArgumentException('ClickHouse queries must be a single non-empty statement without a trailing semicolon.');
        }

        return $sql.' FORMAT '.$format;
    }
}
