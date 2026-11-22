<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Database;

use Generator;
use PDO;
use Webkernel\Database\Query\Grammars\Grammar;
use Webkernel\Database\Query\Processors\Processor;

/** Database operations used by the SQL and ORM builders. */
interface ConnectionInterface
{
    public function getQueryGrammar(): Grammar;

    public function getPostProcessor(): Processor;

    /** @param array<int|string, mixed> $bindings
     *  @return list<object>
     */
    public function select(string $query, array $bindings = []): array;

    /** @param array<int|string, mixed> $bindings
     *  @return Generator<int, object>
     */
    public function cursor(string $query, array $bindings = []): Generator;

    /** @param array<int|string, mixed> $bindings */
    public function insert(string $query, array $bindings = []): bool;

    /** @param array<int|string, mixed> $bindings */
    public function update(string $query, array $bindings = []): int;

    /** @param array<int|string, mixed> $bindings */
    public function delete(string $query, array $bindings = []): int;

    /** @param array<int|string, mixed> $bindings */
    public function affectingStatement(string $query, array $bindings = []): int;

    /** @param array<int|string, mixed> $bindings */
    public function statement(string $query, array $bindings = []): bool;

    /** @param array<int|string, mixed> $bindings
     *  @return list<mixed>
     */
    public function prepareBindings(array $bindings): array;

    public function getPdo(): PDO;

    public function getReadPdo(): PDO;

    public function getDriverName(): string;

    public function getDatabaseName(): ?string;

    public function getName(): ?string;

    public function transactionLevel(): int;

    public function transaction(callable $callback): mixed;
}
