<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Liveview\Features\SupportRateLimiting;

use RuntimeException;

/** Small process-safe rate limiter used by Livewire components. */
final class RateLimiter
{
    public static function tooManyAttempts(string $key, int $max_attempts): bool
    {
        return self::read($key)['attempts'] >= $max_attempts;
    }

    public static function availableIn(string $key): int
    {
        return max(0, self::read($key)['expires_at'] - time());
    }

    public static function hit(string $key, int $decay_seconds = 60): int
    {
        return self::withState($key, static function (array $state) use ($decay_seconds): array {
            $now = time();
            if ($state['expires_at'] <= $now) {
                $state = ['attempts' => 0, 'expires_at' => $now + max(1, $decay_seconds)];
            }
            $state['attempts']++;

            return $state;
        })['attempts'];
    }

    public static function clear(string $key): void
    {
        self::withState($key, static fn (): array => ['attempts' => 0, 'expires_at' => 0]);
    }

    /** @return array{attempts: int, expires_at: int} */
    private static function read(string $key): array
    {
        return self::withState($key, static function (array $state): array {
            return $state['expires_at'] <= time()
                ? ['attempts' => 0, 'expires_at' => 0]
                : $state;
        });
    }

    /**
     * @param callable(array{attempts: int, expires_at: int}): array{attempts: int, expires_at: int} $update
     * @return array{attempts: int, expires_at: int}
     */
    private static function withState(string $key, callable $update): array
    {
        $directory = base_path('runtime/cache/livewire-rate-limits');
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException('Unable to create the Livewire rate-limit cache directory.');
        }

        $path = $directory . '/' . hash('sha256', $key) . '.json';
        $file = fopen($path, 'c+');
        if ($file === false) {
            throw new RuntimeException('Unable to open the Livewire rate-limit cache entry.');
        }

        try {
            if (! flock($file, LOCK_EX)) {
                throw new RuntimeException('Unable to lock the Livewire rate-limit cache entry.');
            }

            rewind($file);
            $contents = stream_get_contents($file);
            $decoded = is_string($contents) && $contents !== '' ? json_decode($contents, true) : null;
            $state = is_array($decoded)
                ? [
                    'attempts' => max(0, (int) ($decoded['attempts'] ?? 0)),
                    'expires_at' => max(0, (int) ($decoded['expires_at'] ?? 0)),
                ]
                : ['attempts' => 0, 'expires_at' => 0];
            $state = $update($state);

            rewind($file);
            if (! ftruncate($file, 0) || fwrite($file, json_encode($state, JSON_THROW_ON_ERROR)) === false) {
                throw new RuntimeException('Unable to persist the Livewire rate-limit cache entry.');
            }
            fflush($file);
            flock($file, LOCK_UN);

            return $state;
        } finally {
            fclose($file);
        }
    }
}
