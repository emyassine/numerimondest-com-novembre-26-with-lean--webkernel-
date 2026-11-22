<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

use Composer\InstalledVersions;
use Composer\Autoload\ClassLoader;
use Webkernel\Lifecycle\Path\LCBasePathInjector;

if (! function_exists('base_path')) {
    /**
     * Absolute path to the project root.
     * Returns the injected root package path with an optional appended path.
     *
     * @param $path string Optional path appended to the project root.
     * @return string Absolute project path.
     * @throws \RuntimeException If the project root cannot be resolved.
     */
    function base_path(string $path = ''): string
    {
        if (! isset($GLOBALS['BASE_PATH'])) {
            LCBasePathInjector::inject();
        }

        if ($path === '') {
            return $GLOBALS['BASE_PATH'];
        }
        return $GLOBALS['BASE_PATH'] . '/' . $path;
    }
}

if (! function_exists('vendor_path')) {
    /**
     * Absolute path to the vendor directory.
     *
     * @param $path string Optional path appended to the vendor directory.
     * @return string Absolute vendor path.
     */
    function vendor_path(string $path = ''): string
    {
        static $vendor = null;

        $vendor ??= (string) array_key_first(ClassLoader::getRegisteredLoaders());

        if ($path === '') {
            return $vendor;
        }

        return $vendor . '/' . $path;
    }
}

if (! function_exists('webkernel_package')) {
    /**
     * Absolute path inside a webkernel/* package registered with Composer.
     * Delegates path resolution entirely to InstalledVersions::getInstallPath()
     * so it is immune to vendor directory layout or __DIR__ depth assumptions.
     * Throws a RuntimeException attributed to the caller when the package
     * is not registered or its install path cannot be resolved.
     *
     * webkernel_package('lifecycle', 'generated/foo.php')
     *   → /absolute/path/to/vendor/webkernel/lifecycle/generated/foo.php
     * @param $package string Webkernel package short name.
     * @param $sub_path string|null Optional path inside the package.
     * @return string Absolute package path.
     * @throws \RuntimeException If the package is not installed or its path is unavailable.
     */
     function webkernel_package(string $package, ?string $sub_path = null): string
     {
         static $resolved = [];

         if (! isset($resolved[$package])) {
             $composer_name = 'webkernel/' . $package;

             try {
                 $raw = InstalledVersions::getInstallPath($composer_name);
             } catch (\OutOfBoundsException) {
                 $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
                 throw new \RuntimeException(sprintf(
                     'Webkernel package "%s" is not registered in Composer (called from %s:%d)',
                     $composer_name,
                     $caller['file'] ?? 'unknown',
                     $caller['line'] ?? 0,
                 ));
             }

             $real = $raw !== null ? realpath($raw) : false;

             if ($real === false || ! is_dir($real)) {
                 $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
                 throw new \RuntimeException(sprintf(
                     'Webkernel package "%s" is registered at "%s" but the directory does not exist (called from %s:%d)',
                     $composer_name,
                     $raw ?? 'null',
                     $caller['file'] ?? 'unknown',
                     $caller['line'] ?? 0,
                 ));
             }

             $resolved[$package] = $real . '/';
         }

         return $sub_path !== null
             ? $resolved[$package] . $sub_path
             : rtrim($resolved[$package], '/');
     }
}
