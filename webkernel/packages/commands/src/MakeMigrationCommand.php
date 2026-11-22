<?php declare(strict_types=1);

namespace Webkernel\Commands;

use Webkernel\Console\Attribute\ConsoleCommand;

/** Creates a readable, reversible application migration skeleton. */
final class MakeMigrationCommand
{
    #[ConsoleCommand(name: 'make:migration', description: 'Create a database migration')]
    public function make(string $name): int
    {
        $name = strtolower(trim($name));
        $name = preg_replace('/[^a-z0-9]+/', '_', $name) ?? '';
        $name = trim($name, '_');
        if ($name === '' || preg_match('/\A[a-z][a-z0-9_]*\z/', $name) !== 1) {
            console()->error('Provide a migration name using letters and numbers.');
            return 2;
        }

        $directory = base_path('app/database/migrations');
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            console()->error('Unable to create the migrations directory.');
            return 1;
        }
        $path = $directory.'/'.date('Ymd_His').'_'.$name.'.php';
        $table = preg_match('/\Acreate_(.+)_table\z/', $name, $match) === 1 ? $match[1] : null;
        $up = is_string($table)
            ? "        Schema::create('{$table}', static function (Blueprint \$table): void {\n            \$table->id();\n        });"
            : '        // Add the schema changes for this migration.';
        $down = is_string($table)
            ? "        Schema::drop('{$table}');"
            : '        // Reverse the schema changes made in up().';
        $source = "<?php declare(strict_types=1);\n\nuse Webkernel\\Database\\Blueprint;\nuse Webkernel\\Database\\Migration;\nuse Webkernel\\Database\\Schema;\n\nreturn new class extends Migration {\n    public function up(): void\n    {\n{$up}\n    }\n\n    public function down(): void\n    {\n{$down}\n    }\n};\n";
        $handle = @fopen($path, 'x');
        if ($handle === false) {
            console()->error('A migration already exists for this timestamp. Try again.');
            return 1;
        }
        try {
            if (fwrite($handle, $source) === false) {
                unlink($path);
                console()->error('Unable to write the migration file.');
                return 1;
            }
        } finally {
            fclose($handle);
        }
        console()->info('Created migration '.$path);
        return 0;
    }
}
