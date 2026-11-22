<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Database;

use InvalidArgumentException;
use RuntimeException;
use Webkernel\Config\Config;

/** Runs application migrations from explicit directories on CLI invocation. */
final class Migrator
{
    /** @return list<string> IDs that were run. */
    public function run(array $directories = []): array
    {
        $this->ensureRepository();
        $ran = $this->ran();
        $pending = array_diff_key($this->discover($this->directories($directories)), $ran);
        if ($pending === []) {
            return [];
        }
        $rows = $this->rows();
        $batch = $rows === [] ? 1 : 1 + max(array_map(static fn (array $row): int => (int) $row['batch'], $rows));
        $completed = [];
        foreach ($pending as $id => $migration) {
            $migration->up();
            Database::table('migrations')->insert(['migration' => $id, 'batch' => $batch]);
            $completed[] = $id;
        }
        return $completed;
    }

    /** Rolls back only the latest completed batch and returns its migration IDs. */
    public function rollback(array $directories = []): array
    {
        $this->ensureRepository();
        $rows = $this->rows();
        if ($rows === []) {
            return [];
        }
        $batch = max(array_map(static fn (array $row): int => (int) $row['batch'], $rows));
        $classes = $this->discover($this->directories($directories));
        $rolled_back = [];
        foreach (array_reverse($rows) as $row) {
            if ((int) $row['batch'] !== $batch) {
                continue;
            }
            $id = (string) $row['migration'];
            if (! isset($classes[$id])) {
                throw new RuntimeException('Cannot find migration '.$id.' to roll it back.');
            }
            $classes[$id]->down();
            Database::table('migrations')->where('migration', $id)->delete();
            $rolled_back[] = $id;
        }
        return $rolled_back;
    }

    /** @return list<string> */
    private function directories(array $directories): array
    {
        if ($directories === []) {
            $configuration = Config::get('database', []);
            $directories = is_array($configuration) ? ($configuration['migrations'] ?? []) : [];
        }
        if (! is_array($directories)) {
            throw new InvalidArgumentException('Database migrations must be configured as a directory list.');
        }
        $paths = [];
        foreach ($directories as $directory) {
            if (! is_string($directory) || $directory === '') {
                throw new InvalidArgumentException('Migration directories must be non-empty paths.');
            }
            $path = str_starts_with($directory, '/') ? $directory : base_path($directory);
            if (! is_dir($path)) {
                throw new InvalidArgumentException('Migration directory does not exist: '.$directory);
            }
            $paths[] = $path;
        }
        return array_values(array_unique($paths));
    }

    /** @param list<string> $directories @return array<string, Migration> */
    private function discover(array $directories): array
    {
        $migrations = [];
        foreach ($directories as $directory) {
            $files = glob(rtrim($directory, '/').'/*.php') ?: [];
            sort($files, SORT_STRING);
            foreach ($files as $file) {
                $id = basename($file, '.php');
                if (preg_match('/\A[a-zA-Z0-9_-]+\z/', $id) !== 1) {
                    throw new InvalidArgumentException('Migration filenames must use letters, numbers, underscores, and dashes.');
                }
                if (isset($migrations[$id])) {
                    throw new InvalidArgumentException('Duplicate migration ID: '.$id);
                }
                $migrations[$id] = $this->loadMigration($file);
            }
        }
        return $migrations;
    }

    private function loadMigration(string $file): Migration
    {
        static $loaded_migrations = [];
        if (isset($loaded_migrations[$file])) {
            $class = $loaded_migrations[$file];
            if ($class instanceof Migration) {
                return clone $class;
            }
            return new $class();
        }
        $before = get_declared_classes();
        $loaded = require_once $file;
        if ($loaded instanceof Migration) {
            $loaded_migrations[$file] = $loaded;
            return $loaded;
        }
        foreach (array_diff(get_declared_classes(), $before) as $class) {
            if (is_subclass_of($class, Migration::class)) {
                $loaded_migrations[$file] = $class;
                return new $class();
            }
        }
        throw new RuntimeException('Migration files must return a Migration instance or declare one concrete class.');
    }

    private function ensureRepository(): void
    {
        if (Schema::hasTable('migrations')) {
            return;
        }
        Schema::create('migrations', static function (Blueprint $table): void {
            $table->increments();
            $table->string('migration');
            $table->integer('batch');
            $table->unique('migration');
        });
    }

    /** @return list<array<string, mixed>> */
    private function rows(): array
    {
        return Database::table('migrations')->orderBy('id')->get();
    }

    /** @return array<string, true> */
    private function ran(): array
    {
        $ran = [];
        foreach ($this->rows() as $row) {
            $ran[(string) $row['migration']] = true;
        }
        return $ran;
    }
}
