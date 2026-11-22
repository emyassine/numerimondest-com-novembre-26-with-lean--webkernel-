<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Commands;

use Webkernel\Console\Attribute\ConsoleCommand;

/** Runs a Composer-installed binary from the application command line. */
final class BinCommand
{
    /**
     * @param $binary string
     * @param $arguments list<string>
     *
     * @return int
     */
    #[ConsoleCommand(name: 'bin', description: 'Run an installed Composer binary; pass binary arguments after --')]
    public function __invoke(string $binary, string ...$arguments): int
    {
        if (preg_match('/\A[A-Za-z0-9][A-Za-z0-9._-]*\z/', $binary) !== 1) {
            fwrite(STDERR, "Invalid binary name.\n");

            return 2;
        }

        foreach ($arguments as $argument) {
            if (str_contains($argument, "\0")) {
                fwrite(STDERR, "Arguments cannot contain null bytes.\n");

                return 2;
            }
        }

        $binary_path = base_path('internal/dependencies/bin/'.$binary);
        if (! is_file($binary_path) || ! is_executable($binary_path)) {
            fwrite(STDERR, 'Installed binary not found: '.$binary."\n");

            return 127;
        }

        $environment = getenv();
        if (! is_array($environment)) {
            $environment = [];
        }
        $environment['TMPDIR'] = tmp_path();
        $process = proc_open(
            [$binary_path, ...$arguments],
            [0 => STDIN, 1 => STDOUT, 2 => STDERR],
            $pipes,
            base_path(),
            $environment,
            ['bypass_shell' => true],
        );
        if (! is_resource($process)) {
            fwrite(STDERR, 'Unable to start installed binary: '.$binary."\n");

            return 127;
        }

        return proc_close($process);
    }
}
