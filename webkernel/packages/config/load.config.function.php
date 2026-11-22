<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

use Webkernel\Config\Config;
use Webkernel\Config\ConfigPaths;
use Webkernel\Config\Repository\ConfigRepository;

$project_root = function_exists('base_path')
    ? \base_path()
    : (\Composer\InstalledVersions::getRootPackage()['install_path'] ?? null);
if (! is_string($project_root) || $project_root === '') {
    throw new \RuntimeException('Composer could not resolve the application root path.');
}

Config::configurePaths(new ConfigPaths($project_root));

if (! \function_exists('config')) {
    /**
     * Accesses configuration values or the repository at hardware speed.
     *
     * config()               → ConfigRepository instance
     * config('app.name')     → mixed
     * config('key', default) → mixed
     *
     * @param $key string|null Dot-notation configuration key.
     * @param $default mixed Fallback value when key is absent.
     *
     * @return ($key is null ? ConfigRepository : mixed)
     */
    function config(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return Config::repository();
        }

        return Config::$items[$key] ?? Config::get($key, $default);
    }
}

if (! \function_exists('config_path')) {
    /**
     * Resolves an absolute path inside the application configuration directory.
     *
     * @param $path string Relative sub-path.
     *
     * @return string
     */
    function config_path(string $path = ''): string
    {
        if ($path === '') {
            return $GLOBALS['WEBKERNEL_CONFIG_PATH'];
        }

        if ($path[0] === '/' || $path[0] === '\\') {
            $path = \ltrim($path, '/\\');
        }

        return $GLOBALS['WEBKERNEL_CONFIG_PATH'] . '/' . $path;
    }
}
