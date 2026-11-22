<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\ChangeWatcher;

use InvalidArgumentException;
use RuntimeException;
use Throwable;
use Webkernel\ChangeWatcher\Registration;

/** Captures content-based fingerprints for local files and directory trees. */
final class SourceSnapshot
{
    /** @var array<string, string> Digests shared by targets in one polling pass. */
    private array $digests = [];

    /**
     * Starts an observation pass with generated cache files excluded.
     *
     * @param $excluded_directory string Canonical absolute cache directory.
     */
    public function __construct(private readonly string $excluded_directory = '')
    {
        clearstatcache(true);
    }

    /**
     * Validates an explicit path without deriving a project root from the working directory.
     *
     * @param $path string Absolute filesystem path.
     * @return string Path with normalized separators; dot segments are rejected.
     * @throws InvalidArgumentException When the path is relative, unsafe or a stream URI.
     */
    public static function absolutePath(string $path): string
    {
        if ($path === '' || str_contains($path, "\0") || str_contains($path, '://')) {
            throw new InvalidArgumentException('Expected an absolute filesystem path.');
        }

        if (DIRECTORY_SEPARATOR === '\\') {
            $path = str_replace('\\', '/', $path);
        }

        if (preg_match('~^(?:[A-Za-z]:/|/)~', $path) !== 1 || str_starts_with($path, '//')) {
            throw new InvalidArgumentException('Expected an absolute local path: ' . $path);
        }

        //> Dot segments can change meaning across symlinks; require explicit paths.
        if (preg_match('~(?:^|/)\.{1,2}(?:/|$)~', $path) === 1) {
            throw new InvalidArgumentException('Resolve dot segments before registration: ' . $path);
        }

        $path = preg_replace('~/+~', '/', $path) ?? $path;

        return $path === '/' || preg_match('~^[A-Za-z]:/$~', $path) === 1
            ? $path
            : rtrim($path, '/');
    }

    /**
     * Fingerprints ordered sources and the consumer's compiler revision.
     *
     * @param $registration Registration Sources to observe.
     * @return string SHA-256 fingerprint.
     * @throws RuntimeException When a source cannot be read safely.
     */
    public function fingerprint(Registration $registration): string
    {
        $parts = [$registration->revision];
        foreach ($registration->sources as $path) {
            $parts[] = $path;
            $parts[] = $this->digest($path);
        }

        return hash('sha256', serialize($parts));
    }

    /**
     * Captures ordered absolute paths and their complete current contents.
     *
     * @param $paths list<string> Files or directories to observe.
     * @return array<string, string> Path-to-content fingerprint map.
     */
    public static function capturePaths(array $paths): array
    {
        $snapshot = new self();
        $fingerprints = [];
        foreach (array_values(array_unique($paths)) as $path) {
            $path = self::absolutePath($path);
            $fingerprints[$path] = $snapshot->digest($path);
        }

        return $fingerprints;
    }

    /**
     * Captures filesystem metadata without reading source file contents.
     *
     * @param $paths list<string> Files and directories to inspect.
     * @return array<string, array{int, int, int, int, int}|null>
     */
    public static function captureMetadataPaths(array $paths): array
    {
        clearstatcache();
        $snapshot = [];
        foreach (array_values(array_unique($paths)) as $path) {
            $path = self::absolutePath($path);
            $stat = @stat($path);
            $snapshot[$path] = $stat === false ? null : [
                (int) $stat['mtime'],
                (int) $stat['size'],
                (int) $stat['ctime'],
                (int) $stat['ino'],
                (int) $stat['mode'],
            ];
        }

        return $snapshot;
    }

    /**
     * Checks whether a previously captured metadata snapshot still matches.
     *
     * @param $snapshot array<string, array{int, int, int, int, int}|null> Path-to-metadata map.
     * @return bool True when every tracked path has the same metadata.
     */
    public static function metadataIsCurrent(array $snapshot): bool
    {
        try {
            return self::captureMetadataPaths(array_keys($snapshot)) === $snapshot;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Returns all paths observed while fingerprinting registered sources.
     *
     * @return array<string, string>
     */
    public function observations(): array
    {
        return $this->digests;
    }

    /**
     * Hashes content and directory membership, retaining missing paths for later discovery.
     *
     * @param $path string Absolute source path.
     * @return string Digest or an explicit missing/excluded marker.
     * @throws RuntimeException For unreadable files, unsupported types or directory symlinks.
     */
    private function digest(string $path): string
    {
        if (isset($this->digests[$path])) {
            return $this->digests[$path];
        }

        $resolved = realpath($path);
        $physical = $resolved === false ? $path : self::absolutePath($resolved);
        if (
            $this->excluded_directory !== ''
            && ($physical === $this->excluded_directory || str_starts_with($physical, $this->excluded_directory . '/'))
        ) {
            return $this->digests[$path] = 'excluded';
        }

        if (is_link($path) && is_dir($path)) {
            throw new RuntimeException('Register the real directory instead of its symlink: ' . $path);
        }

        if (is_file($path)) {
            $hash = @hash_file('sha256', $path);
            if ($hash === false) {
                throw new RuntimeException('Unable to read source: ' . $path);
            }

            return $this->digests[$path] = hash('sha256', "file\0" . $physical . "\0" . $hash);
        }

        if (is_dir($path)) {
            $entries = @scandir($path, SCANDIR_SORT_ASCENDING);
            if ($entries === false) {
                throw new RuntimeException('Unable to read source directory: ' . $path);
            }

            $parts = ['directory', $physical];
            foreach ($entries as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }
                $child = rtrim($path, '/') . '/' . $entry;
                $digest = $this->digest($child);
                if ($digest !== 'excluded') {
                    $parts[] = $entry;
                    $parts[] = $digest;
                }
            }

            return $this->digests[$path] = hash('sha256', serialize($parts));
        }

        if (file_exists($path) || is_link($path)) {
            throw new RuntimeException('Unsupported or broken source: ' . $path);
        }

        //> Unsearchable parents must not turn a permissions failure into a deletion.
        $parent = dirname($path);
        while (!is_dir($parent) && dirname($parent) !== $parent) {
            $parent = dirname($parent);
        }
        if (!is_readable($parent) || (DIRECTORY_SEPARATOR === '/' && !is_executable($parent))) {
            throw new RuntimeException('Unable to inspect source parent: ' . $parent);
        }

        return $this->digests[$path] = 'missing';
    }
}
