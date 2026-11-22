<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Platform\Runtime;

use RuntimeException;

/** Starts a PHP CLI worker without invoking a shell. */
final class PhpWorkerProcess
{
    /** @var list<resource> */
    private static array $processes = [];

    /** @param array<string, string> $environment */
    public static function spawn(string $entrypoint, string $socket_path, string $working_directory, array $environment = []): int
    {
        $entrypoint = realpath($entrypoint) ?: '';
        $working_directory = realpath($working_directory) ?: '';
        $socket_directory = realpath(dirname($socket_path)) ?: '';
        if ($entrypoint === '' || ! is_file($entrypoint) || $working_directory === '' || ! is_dir($working_directory)
            || $socket_directory === '' || ($socket_directory !== $working_directory && ! str_starts_with($socket_directory, $working_directory.DIRECTORY_SEPARATOR))
            || basename($socket_path) === ''
            || preg_match('/[\r\n\x00]/', $socket_path) === 1) {
            throw new RuntimeException('Invalid PHP worker paths.');
        }
        if (! is_executable(PHP_BINARY) || ! function_exists('proc_open')) {
            throw new RuntimeException('PHP CLI process creation is unavailable.');
        }
        $descriptors = [
            0 => ['file', '/dev/null', 'r'],
            1 => ['file', '/dev/null', 'a'],
            2 => ['file', '/dev/null', 'a'],
        ];
        $process_environment = getenv();
        $process_environment = is_array($process_environment) ? [...$process_environment, ...$environment] : $environment;
        $process = proc_open(
            [PHP_BINARY, $entrypoint, $socket_path],
            $descriptors,
            $pipes,
            $working_directory,
            $process_environment === [] ? null : $process_environment,
            ['bypass_shell' => true],
        );
        if (! is_resource($process)) {
            throw new RuntimeException('Unable to start the PHP worker process.');
        }
        $status = proc_get_status($process);
        if (! is_array($status) || ! is_int($status['pid'] ?? null) || $status['pid'] < 1) {
            throw new RuntimeException('Unable to read the PHP worker process ID.');
        }
        self::$processes[] = $process;
        return $status['pid'];
    }
}
