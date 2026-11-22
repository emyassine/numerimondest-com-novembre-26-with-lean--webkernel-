<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Webkernel\Database\Database;
use Webkernel\Database\DatabaseManager;
use Webkernel\Database\Migrator;
use Webkernel\Database\Schema;

final class DatabaseManagerTest extends TestCase
{
    public function testNamedInMemoryConnectionsAreCachedAndUsePreparedStatements(): void
    {
        $manager = new DatabaseManager([
            'default' => 'primary',
            'connections' => [
                'primary' => ['driver' => 'sqlite', 'database' => ':memory:'],
                'reporting' => ['driver' => 'sqlite', 'database' => ':memory:'],
            ],
        ]);

        $connection = $manager->connection();
        self::assertSame($connection, $manager->connection('primary'));
        self::assertNotSame($connection, $manager->connection('reporting'));

        $connection->exec('CREATE TABLE reports (name TEXT NOT NULL)');
        $statement = $connection->prepare('INSERT INTO reports (name) VALUES (:name)');
        $statement->execute(['name' => 'safe report']);

        self::assertSame('safe report', $connection->query('SELECT name FROM reports')->fetchColumn());
    }

    public function testSqliteRejectsPathsOutsideTheApplicationRoot(): void
    {
        $manager = new DatabaseManager([
            'default' => 'sqlite',
            'connections' => [
                'sqlite' => ['driver' => 'sqlite', 'database' => '../outside.sqlite'],
            ],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $manager->connection();
    }

    public function testClickHouseUsesItsSpecializedClientInsteadOfPdo(): void
    {
        $manager = new DatabaseManager([
            'default' => 'analytics',
            'connections' => [
                'analytics' => ['driver' => 'clickhouse', 'host' => '127.0.0.1', 'database' => 'default'],
            ],
        ]);

        self::assertSame($manager->clickhouse(), $manager->clickhouse('analytics'));
        $this->expectException(RuntimeException::class);
        $manager->connection('analytics');
    }

    public function testMigrationsRunOnceAndRollbackTheLatestBatch(): void
    {
        Database::configure([
            'default' => 'testing',
            'connections' => ['testing' => ['driver' => 'sqlite', 'database' => ':memory:']],
        ]);
        $migrator = new Migrator();
        $directory = __DIR__.'/fixtures/migrations';

        self::assertSame(['20261019_create_events'], $migrator->run([$directory]));
        self::assertTrue(Schema::hasTable('events'));
        self::assertSame([], $migrator->run([$directory]));
        self::assertSame(['20261019_create_events'], $migrator->rollback([$directory]));
        self::assertFalse(Schema::hasTable('events'));
    }
}
