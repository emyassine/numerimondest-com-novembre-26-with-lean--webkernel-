<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\ChangeWatcher;

use InvalidArgumentException;
use RuntimeException;
use Webkernel\ChangeWatcher\Publication;
use Webkernel\ChangeWatcher\SourceSnapshot;

/** Stores opaque artifacts and atomically publishes a pointer for each target. */
final readonly class CacheStore
{
    /** @var string Canonical directory dedicated to one application and scope. */
    public string $directory;

    /**
     * Opens or creates a private cache directory supplied by the application.
     *
     * @param $directory string Absolute cache directory, normally under runtime/cache.
     * @throws InvalidArgumentException When the path is invalid or a filesystem root.
     * @throws RuntimeException When the directory cannot be created or resolved.
     */
    public function __construct(string $directory)
    {
        $directory = SourceSnapshot::absolutePath($directory);
        if ($directory === '/' || preg_match('~^[A-Za-z]:/$~', $directory) === 1) {
            throw new InvalidArgumentException('A dedicated cache directory is required.');
        }
        if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create cache directory: ' . $directory);
        }
        $resolved = realpath($directory);
        if ($resolved === false) {
            throw new RuntimeException('Unable to resolve cache directory: ' . $directory);
        }
        $resolved = SourceSnapshot::absolutePath($resolved);
        if ($resolved === '/' || preg_match('~^[A-Za-z]:/$~', $resolved) === 1) {
            throw new InvalidArgumentException('The cache directory must not resolve to a filesystem root.');
        }
        $this->directory = $resolved;
    }

    /**
     * Reads the last published pointer and verifies the referenced artifact bytes.
     *
     * This performs filesystem I/O and does not check source freshness.
     *
     * @param $id string Consumer-defined target identifier.
     * @return Publication|null Null when no complete artifact exists.
     * @throws RuntimeException When published metadata is unreadable or malformed.
     */
    public function read(string $id): ?Publication
    {
        $directory = $this->targetDirectory($id);
        $manifest = $directory . '/current.json';
        if (!is_file($manifest)) {
            return null;
        }
        $contents = @file_get_contents($manifest);
        if ($contents === false) {
            throw new RuntimeException('Unable to read publication: ' . $manifest);
        }
        $data = json_decode($contents, true);
        if (
            !is_array($data)
            || ($data['format'] ?? null) !== 1
            || ($data['id'] ?? null) !== $id
            || !is_string($data['fingerprint'] ?? null)
            || !is_string($data['version'] ?? null)
            || preg_match('/\A[a-f0-9]{64}\z/', $data['fingerprint']) !== 1
            || preg_match('/\A[a-f0-9]{64}\z/', $data['version']) !== 1
        ) {
            throw new RuntimeException('Invalid publication metadata: ' . $manifest);
        }

        $path = $directory . '/' . $data['version'] . '.cache';
        if (!is_file($path) || @hash_file('sha256', $path) !== $data['version']) {
            return null;
        }

        return new Publication($id, $data['fingerprint'], $data['version'], $path);
    }

    /**
     * Reads a published pointer for a request path without rehashing its immutable artifact.
     * Use only for locally published artifacts with a trusted runtime cache directory.
     */
    public function readPublished(string $id): ?Publication
    {
        $directory = $this->targetDirectory($id);
        $manifest = @file_get_contents($directory.'/current.json');
        if ($manifest === false) { return null; }
        $data = json_decode($manifest, true);
        if (
            ! is_array($data)
            || ($data['format'] ?? null) !== 1
            || ($data['id'] ?? null) !== $id
            || ! is_string($data['fingerprint'] ?? null)
            || ! is_string($data['version'] ?? null)
            || preg_match('/\A[a-f0-9]{64}\z/', $data['fingerprint']) !== 1
            || preg_match('/\A[a-f0-9]{64}\z/', $data['version']) !== 1
        ) {
            throw new RuntimeException('Invalid publication metadata: '.$directory.'/current.json');
        }
        return new Publication($id, $data['fingerprint'], $data['version'], $directory.'/'.$data['version'].'.cache');
    }

    /**
     * Writes complete artifact bytes before publishing their content-addressed path.
     *
     * @param $id string Consumer-defined target identifier.
     * @param $fingerprint string SHA-256 digest of the build inputs.
     * @param $contents string Opaque bytes produced by the consumer's compiler.
     * @return Publication Published artifact reference.
     * @throws InvalidArgumentException When the fingerprint is malformed.
     * @throws RuntimeException When persistence fails.
     */
    public function publish(string $id, string $fingerprint, string $contents): Publication
    {
        if (preg_match('/\A[a-f0-9]{64}\z/', $fingerprint) !== 1) {
            throw new InvalidArgumentException('Expected a SHA-256 source fingerprint.');
        }
        $directory = $this->targetDirectory($id);
        if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create target directory: ' . $directory);
        }

        $version = hash('sha256', $contents);
        $path = $directory . '/' . $version . '.cache';
        if (!is_file($path) || @hash_file('sha256', $path) !== $version) {
            $this->atomicWrite($path, $contents);
        }

        $metadata = json_encode([
            'format' => 1,
            'id' => $id,
            'fingerprint' => $fingerprint,
            'version' => $version,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $this->atomicWrite($directory . '/current.json', $metadata . "\n");

        return new Publication($id, $fingerprint, $version, $path);
    }

    /**
     * Maps an identifier to its own directory without allowing path traversal.
     *
     * @param $id string Non-empty UTF-8 identifier.
     * @return string Absolute target directory.
     * @throws InvalidArgumentException When the identifier is invalid.
     */
    private function targetDirectory(string $id): string
    {
        if ($id === '' || str_contains($id, "\0") || preg_match('//u', $id) !== 1) {
            throw new InvalidArgumentException('Expected a non-empty UTF-8 target identifier.');
        }

        return $this->directory . '/' . hash('sha256', $id);
    }

    /**
     * Atomically replaces one file from a temporary file in the same directory.
     *
     * @param $path string Destination inside this cache store.
     * @param $contents string Complete output bytes.
     * @return void
     * @throws RuntimeException When a write is incomplete or replacement fails.
     */
    private function atomicWrite(string $path, string $contents): void
    {
        $temporary = $path . '.' . bin2hex(random_bytes(12)) . '.tmp';
        $handle = @fopen($temporary, 'xb');
        if ($handle === false) {
            throw new RuntimeException('Unable to create temporary artifact: ' . $temporary);
        }

        try {
            if (!@chmod($temporary, 0600)) {
                throw new RuntimeException('Unable to protect temporary artifact: ' . $temporary);
            }
            $offset = 0;
            $length = strlen($contents);
            while ($offset < $length) {
                $written = fwrite($handle, substr($contents, $offset));
                if ($written === false || $written === 0) {
                    throw new RuntimeException('Incomplete artifact write: ' . $temporary);
                }
                $offset += $written;
            }
            if (!fflush($handle)) {
                throw new RuntimeException('Unable to flush artifact: ' . $temporary);
            }
            fclose($handle);
            $handle = null;
            if (!@rename($temporary, $path)) {
                throw new RuntimeException('Unable to publish artifact: ' . $path);
            }
            clearstatcache(true, $path);
            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($path, true);
            }
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
            if (is_file($temporary)) {
                @unlink($temporary);
            }
        }
    }
}
