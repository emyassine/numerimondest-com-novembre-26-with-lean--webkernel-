<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

use Composer\InstalledVersions;

$GLOBALS['webkernel_request_started_at'] ??= hrtime(true);

if (! function_exists('request_time')) {
    /** Returns monotonic milliseconds elapsed since request entry at the time of this call. */
    function request_time(): float
    {
        $started_at = $GLOBALS['webkernel_request_started_at'] ?? null;
        if (! is_int($started_at)) {
            throw new RuntimeException('The request start time is unavailable.');
        }

        return (hrtime(true) - $started_at) / 1_000_000;
    }
}

if (! function_exists('base_path')) {
    /** Resolves an application path from Composer's authoritative project root. */
    function base_path(string $path = ''): string
    {
        $root_path = InstalledVersions::getRootPackage()['install_path'] ?? null;
        $root_path = is_string($root_path) ? realpath($root_path) : false;
        if ($root_path === false) {
            throw new \RuntimeException('Composer could not resolve the application root path.');
        }

        return $path === '' ? $root_path : $root_path . '/' . ltrim($path, '/\\');
    }
}

if (! function_exists('public_path')) {
    /** Resolves a path inside the application's public document root. */
    function public_path(string $path = ''): string
    {
        return base_path($path === '' ? 'public' : 'public/' . ltrim($path, '/\\'));
    }
}

if (! function_exists('asset')) {
    /** Builds a public asset URL through the route URL generator. */
    function asset(string $path, ?bool $secure = null): string
    {
        return app(\Webkernel\Route\UrlGenerator::class)->asset($path, $secure);
    }
}

if (! function_exists('resource_path')) {
    /** Resolves application-owned resource files from Webkernel's paths configuration. */
    function resource_path(string $path = ''): string
    {
        $resources = config('paths.resources', 'app/resources');

        if (! is_string($resources) || $resources === '') {
            throw new RuntimeException('The application resources path is not configured.');
        }

        return base_path($path === '' ? $resources : rtrim($resources, '/\\').'/'.ltrim($path, '/\\'));
    }
}

if (! function_exists('vendor_path')) {
    /** Resolves the Composer vendor directory from the installed autoloader location. */
    function vendor_path(string $path = ''): string
    {
        $autoload_file = (new \ReflectionClass(InstalledVersions::class))->getFileName();
        if (! is_string($autoload_file)) {
            throw new \RuntimeException('Composer vendor directory could not be resolved.');
        }

        $directory = dirname($autoload_file, 2);
        return $path === '' ? $directory : $directory.'/'.ltrim($path, '/\\');
    }
}

if (! function_exists('webkernel_packages')) {
    /** Returns compiled package metadata and refreshes it when a package manifest changes. */
    function webkernel_packages(): array
    {
        static $compiled_packages = null;
        static $manifest_snapshot = null;

        $manifest_path = base_path() . '/runtime/cache/declarations/webkernel_packages.php';
        if (! is_file($manifest_path)) {
            throw new RuntimeException('The Webkernel package catalog is unavailable; run Composer autoload generation.');
        }

        clearstatcache(true, $manifest_path);
        $stat = @stat($manifest_path);
        $current_snapshot = $stat === false ? null : [
            (int) $stat['mtime'],
            (int) $stat['size'],
            (int) $stat['ctime'],
            (int) $stat['ino'],
        ];
        if (! is_array($compiled_packages) || $manifest_snapshot !== $current_snapshot) {
            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($manifest_path, true);
            }
            $manifest = require $manifest_path;
            $compiled_packages = is_array($manifest) ? $manifest : [];
            $manifest_snapshot = $current_snapshot;
        }

        return $compiled_packages;
    }
}

if (! function_exists('tmp_path')) {
    /** Resolves and creates the configured application temporary directory. */
    function tmp_path(string $path = ''): string
    {
        $temporary_path = config('paths.temporary', 'runtime/tmp');
        if (! is_string($temporary_path) || $temporary_path === '' || str_contains($temporary_path, "\0")) {
            throw new \RuntimeException('The configured temporary path is invalid.');
        }
        $segments = explode('/', str_replace('\\', '/', $temporary_path));
        if (str_starts_with($temporary_path, '/') || in_array('..', $segments, true)) {
            throw new \RuntimeException('The configured temporary path must stay inside the application root.');
        }

        $directory = base_path($temporary_path);
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new \RuntimeException('Unable to create the configured temporary directory: '.$directory);
        }
        if (! is_writable($directory)) {
            throw new \RuntimeException('The configured temporary directory is not writable: '.$directory);
        }

        if ($path === '') {
            return $directory;
        }
        $relative_path = str_replace('\\', '/', $path);
        if (str_starts_with($relative_path, '/') || in_array('..', explode('/', $relative_path), true)) {
            throw new \InvalidArgumentException('Temporary file paths must stay inside the configured temporary directory.');
        }

        return $directory.'/'.$relative_path;
    }
}
