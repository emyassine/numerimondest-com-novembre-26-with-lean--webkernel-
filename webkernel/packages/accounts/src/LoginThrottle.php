<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Accounts;

use RuntimeException;

/** Applies a shared file-locked limit to login attempts across sessions. */
final class LoginThrottle
{
    private const int LIMIT = 5;
    private const int WINDOW = 300;

    public function blocked(string $email, string $remote_address): bool
    {
        return $this->change($email, $remote_address, false);
    }

    public function failed(string $email, string $remote_address): void
    {
        $this->change($email, $remote_address, true);
    }

    public function succeeded(string $email, string $remote_address): void
    {
        $this->change($email, $remote_address, null);
    }

    private function change(string $email, string $remote_address, ?bool $failure): bool
    {
        $directory = base_path('runtime/cache/accounts');
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException('Login protection storage is unavailable.');
        }
        $path = $directory.'/'.hash('sha256', strtolower(trim($email)).'|'.$remote_address).'.json';
        $file = fopen($path, 'c+');
        if (! is_resource($file)) { throw new RuntimeException('Login protection storage is unavailable.'); }
        try {
            if (! flock($file, LOCK_EX)) { throw new RuntimeException('Login protection storage is unavailable.'); }
            $contents = stream_get_contents($file);
            $state = is_string($contents) ? json_decode($contents, true) : null;
            $now = time();
            $state = is_array($state) && ((int) ($state['until'] ?? 0) > $now || $now - (int) ($state['started'] ?? 0) < self::WINDOW)
                ? $state
                : ['count' => 0, 'until' => 0, 'started' => $now];
            if ($failure === null) {
                $state = ['count' => 0, 'until' => 0, 'started' => $now];
            } elseif ($failure) {
                $state['count'] = (int) $state['count'] + 1;
                if ($state['count'] >= self::LIMIT) { $state['until'] = $now + self::WINDOW; }
            }
            rewind($file);
            if (! ftruncate($file, 0) || fwrite($file, json_encode($state, JSON_THROW_ON_ERROR)) === false || ! fflush($file)) {
                throw new RuntimeException('Login protection storage could not be updated.');
            }
            return (int) $state['until'] > $now || (int) $state['count'] >= self::LIMIT;
        } finally {
            flock($file, LOCK_UN);
            fclose($file);
        }
    }
}
