<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Database\Console;

use Webkernel\Console\Attribute\ConsoleCommand;
use Webkernel\Console\ExitCode;
use Webkernel\Database\Migrator;

/** Runs configured migration directories from the Webkernel console. */
final readonly class MigrateCommand
{
    public function __construct(private Migrator $migrator)
    {
    }

    #[ConsoleCommand(name: 'migrate:run', description: 'Run pending database migrations')]
    public function migrate(): ExitCode
    {
        $migrations = $this->migrator->run();
        if ($migrations === []) {
            console()->info('nothing to migrate');
            return ExitCode::SUCCESS;
        }
        foreach ($migrations as $migration) {
            console()->info('migrated '.$migration);
        }
        return ExitCode::SUCCESS;
    }

    #[ConsoleCommand(name: 'migrate:rollback', description: 'Roll back the latest database migration batch')]
    public function rollback(): ExitCode
    {
        $migrations = $this->migrator->rollback();
        if ($migrations === []) {
            console()->info('nothing to roll back');
            return ExitCode::SUCCESS;
        }
        foreach ($migrations as $migration) {
            console()->info('rolled back '.$migration);
        }
        return ExitCode::SUCCESS;
    }
}
