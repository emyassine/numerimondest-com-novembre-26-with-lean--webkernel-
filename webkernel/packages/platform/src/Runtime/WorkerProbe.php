<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Platform\Runtime;

use RuntimeException;

/** Runs and persists the host capability checks for the PHP worker tier. */
final class WorkerProbe
{
    private const string PROBE_NAME = 'php-worker-probe';

    /** @var list<resource> */
    private static array $processes = [];

    public function __construct(private readonly string $private_directory)
    {
    }

    /** Starts a short-lived detached probe; call verify() on a later request. */
    public function begin(): array
    {
        $this->ensurePrivateDirectory();
        if (! Capability::ProcOpen->isAvailable()) {
            return $this->publish(['php_worker' => false, 'reason' => 'proc_open_unavailable']);
        }

        $this->ensurePrivateDirectory();
        $pipes = [];
        $process = proc_open(
            [PHP_BINARY, '-r', 'sleep(30);'],
            [0 => ['file', '/dev/null', 'r'], 1 => ['file', '/dev/null', 'a'], 2 => ['file', '/dev/null', 'a']],
            $pipes,
            $this->private_directory,
            null,
            ['bypass_shell' => true],
        );
        if (! is_resource($process)) {
            return $this->publish(['php_worker' => false, 'reason' => 'spawn_failed']);
        }
        self::$processes[] = $process;

        $status = proc_get_status($process);
        if (! is_array($status) || ! is_int($status['pid'] ?? null) || $status['pid'] < 1) {
            return $this->publish(['php_worker' => false, 'reason' => 'pid_unavailable']);
        }

        $this->publish(['php_worker' => null, 'pid' => $status['pid'], 'started_at' => time()]);
        // Deliberately retain the process resource for this request. The subsequent request
        // checks whether the child survived the SAPI request boundary.
        return ['php_worker' => null, 'pid' => $status['pid'], 'started_at' => time()];
    }

    /** Verifies process survival and a private Unix socket round trip on a later request. */
    public function verify(): array
    {
        $state = $this->readState();
        $pid = $state['pid'] ?? null;
        if (! is_int($pid) || $pid < 1 || ! $this->processIsAlive($pid)) {
            return $this->publish(['php_worker' => false, 'reason' => 'child_did_not_survive']);
        }

        $socket_ms = $this->measureUnixSocketRoundTrip();
        return $this->publish([
            'php_worker' => $socket_ms !== null,
            'pid' => $pid,
            'socket_round_trip_ms' => $socket_ms,
            'checked_at' => time(),
            'reason' => $socket_ms === null ? 'unix_socket_unavailable' : null,
        ]);
    }

    /** @return array<string, int|float|string|bool|null> */
    public function result(): array
    {
        return $this->readState();
    }

    /** @return array<string, int|float|string|bool|null> */
    private function publish(array $state): array
    {
        $this->ensurePrivateDirectory();
        $state_path = $this->private_directory.'/'.self::PROBE_NAME.'.php';
        $temporary_path = $state_path.'.'.bin2hex(random_bytes(6)).'.tmp';
        $source = '<?php return '.var_export($state, true).';'.PHP_EOL;
        if (file_put_contents($temporary_path, $source, LOCK_EX) === false || ! rename($temporary_path, $state_path)) {
            throw new RuntimeException('Unable to persist PHP worker probe results.');
        }
        chmod($state_path, 0600);
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($state_path, true);
        }

        return $state;
    }

    /** @return array<string, int|float|string|bool|null> */
    private function readState(): array
    {
        $path = $this->private_directory.'/'.self::PROBE_NAME.'.php';
        if (! is_file($path) || ! is_readable($path)) {
            return [];
        }
        $state = require $path;
        return is_array($state) ? $state : [];
    }

    private function processIsAlive(int $pid): bool
    {
        if (function_exists('posix_kill')) {
            return @posix_kill($pid, 0);
        }
        return is_file('/proc/'.$pid.'/stat');
    }

    private function measureUnixSocketRoundTrip(): float|null
    {
        $socket_path = $this->private_directory.'/'.self::PROBE_NAME.'.sock';
        @unlink($socket_path);
        $server = @stream_socket_server('unix://'.$socket_path, $error_code, $error_message, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN);
        if (! is_resource($server)) {
            return null;
        }
        chmod($socket_path, 0600);
        $client = @stream_socket_client('unix://'.$socket_path, $error_code, $error_message, 0.2);
        if (! is_resource($client)) {
            fclose($server);
            @unlink($socket_path);
            return null;
        }
        $started_at = hrtime(true);
        $peer = @stream_socket_accept($server, 0.2);
        $elapsed_ms = null;
        if (is_resource($peer) && fwrite($client, "p") === 1 && fread($peer, 1) === 'p') {
            $elapsed_ms = (hrtime(true) - $started_at) / 1e6;
        }
        if (is_resource($peer)) { fclose($peer); }
        fclose($client);
        fclose($server);
        @unlink($socket_path);
        return $elapsed_ms;
    }

    private function ensurePrivateDirectory(): void
    {
        if (! is_dir($this->private_directory) && ! mkdir($this->private_directory, 0700, true) && ! is_dir($this->private_directory)) {
            throw new RuntimeException('Unable to create the private PHP worker state directory.');
        }
        chmod($this->private_directory, 0700);
    }
}
