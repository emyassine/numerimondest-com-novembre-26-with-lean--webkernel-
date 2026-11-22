<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Platform\Runtime;

use RuntimeException;

/** Coalesces lazy worker starts across concurrent PHP-FPM requests. */
final readonly class PhpWorkerSupervisor
{
    public function __construct(
        private string $private_directory,
        private string $entrypoint,
        private string $socket_name = 'panel-worker.sock',
    ) {
        if (preg_match('/\A[a-zA-Z0-9._-]{1,80}\z/', $socket_name) !== 1) {
            throw new RuntimeException('Invalid PHP worker socket name.');
        }
    }

    /** Starts at most one worker per two seconds when the socket is missing. */
    public function startIfMissing(): int|null
    {
        if (filetype($this->private_directory.'/'.$this->socket_name) === 'socket') {
            return null;
        }
        if (! is_dir($this->private_directory)) {
            throw new RuntimeException('The PHP worker private directory is unavailable.');
        }
        $lock_path = $this->private_directory.'/worker-start.lock';
        $handle = fopen($lock_path, 'c');
        if (! is_resource($handle) || ! flock($handle, LOCK_EX)) {
            if (is_resource($handle)) { fclose($handle); }
            throw new RuntimeException('Unable to lock PHP worker startup.');
        }
        chmod($lock_path, 0600);
        try {
            if (filetype($this->private_directory.'/'.$this->socket_name) === 'socket') {
                return null;
            }
            $state_path = $this->private_directory.'/worker-started-at';
            $last_started_at = is_file($state_path) ? (int) file_get_contents($state_path) : 0;
            if ($last_started_at > 0 && time() - $last_started_at < 2) {
                return null;
            }
            $pid = PhpWorkerProcess::spawn(
                $this->entrypoint,
                $this->private_directory.'/'.$this->socket_name,
                $this->private_directory,
            );
            file_put_contents($state_path, (string) time(), LOCK_EX);
            chmod($state_path, 0600);
            return $pid;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
