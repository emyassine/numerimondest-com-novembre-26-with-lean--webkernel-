<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with the source code.

namespace Webkernel\ServiceProvider\Deployment;

use RuntimeException;

/** Reads the root deployment receipt and resolves immutable artifact paths. */
final class DeploymentLock
{
    private static bool $build_mode = false;
    private static bool $recovery_deployment_requested = false;

    public static function beginBuild(): void
    {
        self::$build_mode = true;
    }

    public static function endBuild(): void
    {
        self::$build_mode = false;
    }

    /** Drops a stale deployment receipt so the current request can use source routes. */
    public static function invalidate(string $root_path): void
    {
        $path = rtrim($root_path, '/\\').'/platform-cache.lock';
        if (is_file($path)) {
            @unlink($path);
            if (function_exists('opcache_invalidate')) {
                @opcache_invalidate($path, true);
            }
        }
        self::requestSilentRecoveryDeployment($root_path);
    }

    /** @return array{format: int, generation: string, artifacts: array<string, string>}|null */
    public static function read(string $root_path): ?array
    {
        if (self::$build_mode) {
            return null;
        }
        $path = rtrim($root_path, '/\\').'/platform-cache.lock';
        clearstatcache(true, $path);
        if (! is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);
        $payload = is_string($contents) ? json_decode($contents, true) : null;
        if (
            ! is_array($payload)
            || ($payload['format'] ?? null) !== 1
            || ! is_string($payload['generation'] ?? null)
            || preg_match('/\A[a-f0-9]{32}\z/', $payload['generation']) !== 1
            || ! is_array($payload['artifacts'] ?? null)
        ) {
            throw new RuntimeException('The platform-cache.lock file is invalid. Run ./platform deploy to rebuild it.');
        }

        foreach ($payload['artifacts'] as $key => $relative_path) {
            if (
                ! is_string($key)
                || preg_match('/\A[a-z][a-z0-9_-]*\z/', $key) !== 1
                || ! is_string($relative_path)
                || str_starts_with($relative_path, '/')
                || in_array('..', explode('/', str_replace('\\', '/', $relative_path)), true)
                || ! str_starts_with($relative_path, 'runtime/cache/deploy/'.$payload['generation'].'/')
            ) {
                throw new RuntimeException('The platform-cache.lock file contains an invalid artifact path.');
            }
        }

        // Runtime caches may be cleared independently of the deployment receipt.
        // An incomplete generation must fall back to dynamic mode so HTTP and CLI
        // bootstraps stay available and the next deploy can publish a fresh one.
        foreach ($payload['artifacts'] as $relative_path) {
            if (! is_dir(rtrim($root_path, '/\\').'/'.$relative_path)) {
                self::requestSilentRecoveryDeployment($root_path);

                return null;
            }
        }

        return $payload;
    }

    public static function artifactDirectory(string $root_path, string $key): ?string
    {
        $deployment = self::read($root_path);
        if ($deployment === null) {
            return null;
        }
        $relative = $deployment['artifacts'][$key] ?? null;
        if (! is_string($relative)) {
            return null;
        }

        $path = rtrim($root_path, '/\\').'/'.$relative;
        if (! is_dir($path)) {
            return null;
        }

        return $path;
    }

    private static function requestSilentRecoveryDeployment(string $root_path): void
    {
        if (
            self::$recovery_deployment_requested
            || self::$build_mode
            || PHP_SAPI === 'cli'
            || ! function_exists('proc_open')
            || ! is_file('/bin/sh')
        ) {
            return;
        }

        self::$recovery_deployment_requested = true;
        $deploy_root = rtrim($root_path, '/\\').'/runtime/cache/deploy';
        if (! is_dir($deploy_root) && ! @mkdir($deploy_root, 0775, true) && ! is_dir($deploy_root)) {
            return;
        }

        $platform_script = rtrim($root_path, '/\\').'/platform';
        if (! is_file($platform_script) || ! is_executable($platform_script)) {
            return;
        }

        $marker = $deploy_root.'/recovery.requested';
        $handle = @fopen($marker, 'c+');
        if (! is_resource($handle) || ! @flock($handle, LOCK_EX | LOCK_NB)) {
            if (is_resource($handle)) {
                fclose($handle);
            }

            return;
        }

        $metadata = fstat($handle);
        if (is_array($metadata) && ($metadata['size'] ?? 0) > 0 && time() - (int) ($metadata['mtime'] ?? 0) < 180) {
            flock($handle, LOCK_UN);
            fclose($handle);

            return;
        }

        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, (string) time());
        fflush($handle);

        $process = @proc_open(
            [
                '/bin/sh',
                '-c',
                'nohup "$1" "$2" deploy >/dev/null 2>&1 </dev/null &',
                'webkernel-recovery',
                PHP_BINARY,
                $platform_script,
            ],
            [
                0 => ['file', '/dev/null', 'r'],
                1 => ['file', '/dev/null', 'a'],
                2 => ['file', '/dev/null', 'a'],
            ],
            $pipes,
            $root_path,
            null,
            ['bypass_shell' => true],
        );

        if (is_resource($process)) {
            @proc_close($process);
        } else {
            ftruncate($handle, 0);
            fflush($handle);
        }

        flock($handle, LOCK_UN);
        fclose($handle);
    }

    /** Publishes the active generation only after all builders have completed. */
    public static function publish(string $root_path, string $generation, array $artifacts): void
    {
        $path = rtrim($root_path, '/\\').'/platform-cache.lock';
        $payload = ['format' => 1, 'generation' => $generation, 'artifacts' => $artifacts];
        $contents = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;
        $temporary = $path.'.'.bin2hex(random_bytes(8)).'.tmp';
        if (@file_put_contents($temporary, $contents, LOCK_EX) !== strlen($contents)) {
            @unlink($temporary);
            throw new RuntimeException('Unable to write the deployment receipt.');
        }
        if (! @rename($temporary, $path)) {
            @unlink($temporary);
            throw new RuntimeException('Unable to publish platform-cache.lock.');
        }
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($path, true);
        }
    }
}
