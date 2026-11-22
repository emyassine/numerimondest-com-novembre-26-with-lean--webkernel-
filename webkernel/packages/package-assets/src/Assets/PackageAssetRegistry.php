<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Codebase\Assets;

/** Resolves package asset paths from Composer-compiled declarations. */
final class PackageAssetRegistry
{
    /** @var array<string, array{package_root: string, roots: list<string>}>|null */
    private static ?array $packages = null;

    private static string $manifest_path = '';

    /** @var array{mtime: int, ctime: int, size: int, inode: int}|null */
    private static ?array $manifest_snapshot = null;

    private static bool $checked_this_request = false;

    private function __construct() {}

    public static function beginRequest(): void
    {
        self::$checked_this_request = false;
    }

    public static function isDeclared(string $package_name, string $package_relative_path): bool
    {
        $declaration = self::packages()[$package_name] ?? null;
        if (! is_array($declaration) || ! self::isSafeRelativePath($package_relative_path)) {
            return false;
        }
        $candidate = rtrim($declaration['package_root'], '/').'/'.$package_relative_path;
        foreach ($declaration['roots'] as $root) {
            if (is_string($root) && str_starts_with($candidate, rtrim($root, '/').'/')) {
                return true;
            }
        }

        return false;
    }

    public static function resolve(string $package_name, string $package_relative_path): ?string
    {
        $declaration = self::packages()[$package_name] ?? null;
        if (! is_array($declaration) || ! self::isSafeRelativePath($package_relative_path)) {
            return null;
        }

        $package_root = realpath($declaration['package_root']);
        if (! is_string($package_root)) {
            return null;
        }
        $candidate = realpath($package_root.'/'.$package_relative_path);
        if (! is_string($candidate) || ! is_file($candidate)) {
            return null;
        }

        foreach ($declaration['roots'] as $root) {
            $resolved_root = realpath($root);
            if (
                is_string($resolved_root)
                && str_starts_with($resolved_root, $package_root.'/')
                && str_starts_with($candidate, $resolved_root.'/')
            ) {
                return $candidate;
            }
        }

        return null;
    }

    private static function packages(): array
    {
        if (self::$checked_this_request && self::$packages !== null) {
            return self::$packages;
        }

        self::$checked_this_request = true;
        if (self::$manifest_path === '') {
            self::$manifest_path = base_path().'/runtime/cache/declarations/webkernel_asset_roots.php';
        }

        clearstatcache(true, self::$manifest_path);
        $stat = @stat(self::$manifest_path);
        if ($stat === false) {
            self::$packages = [];
            self::$manifest_snapshot = null;

            return self::$packages;
        }

        $snapshot = [
            'mtime' => (int) $stat['mtime'],
            'ctime' => (int) $stat['ctime'],
            'size' => (int) $stat['size'],
            'inode' => (int) $stat['ino'],
        ];
        if (self::$packages !== null && self::$manifest_snapshot === $snapshot) {
            return self::$packages;
        }

        $cache_key = 'webkernel:package-asset-roots:'.hash('sha256', self::$manifest_path);
        $cached = null;
        $cache_hit = false;
        $apcu_enabled = function_exists('apcu_fetch') && (bool) ini_get('apc.enabled');
        if ($apcu_enabled) {
            $cached = apcu_fetch($cache_key, $cache_hit);
            if (
                $cache_hit
                && is_array($cached)
                && ($cached['snapshot'] ?? null) === $snapshot
                && is_array($cached['packages'] ?? null)
            ) {
                self::$manifest_snapshot = $snapshot;
                self::$packages = $cached['packages'];

                return self::$packages;
            }
        }

        $manifest_changed = self::$manifest_snapshot !== null && self::$manifest_snapshot !== $snapshot;
        if ($apcu_enabled && (! $cache_hit || (is_array($cached) && ($cached['snapshot'] ?? null) !== $snapshot))) {
            $manifest_changed = true;
        }
        if ($manifest_changed && function_exists('opcache_invalidate')) {
            opcache_invalidate(self::$manifest_path, true);
        }

        self::$manifest_snapshot = $snapshot;
        self::$packages = self::readManifest();
        if ($apcu_enabled && function_exists('apcu_store')) {
            apcu_store($cache_key, ['snapshot' => $snapshot, 'packages' => self::$packages]);
        }

        return self::$packages;
    }

    /** @return array<string, array{package_root: string, roots: list<string>}> */
    private static function readManifest(): array
    {
        if (! is_file(self::$manifest_path)) {
            return self::$packages = [];
        }
        $packages = require self::$manifest_path;
        if (! is_array($packages)) {
            return self::$packages = [];
        }

        $valid = [];
        foreach ($packages as $name => $declaration) {
            if (
                is_string($name)
                && is_array($declaration)
                && is_string($declaration['package_root'] ?? null)
                && is_array($declaration['roots'] ?? null)
                && array_is_list($declaration['roots'])
            ) {
                $valid[$name] = [
                    'package_root' => $declaration['package_root'],
                    'roots' => array_values(array_filter($declaration['roots'], is_string(...))),
                ];
            }
        }

        return self::$packages = $valid;
    }

    private static function isSafeRelativePath(string $path): bool
    {
        if ($path === '' || str_contains($path, "\0") || str_contains($path, '\\') || str_starts_with($path, '/')) {
            return false;
        }
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return false;
            }
        }

        return true;
    }
}
