<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Config;

/**
 * Holds the filesystem locations used by the configuration system.
 */
final readonly class ConfigPaths
{
    private string $resolved_config_directory;
    private string $resolved_cache_directory;
    private string $resolved_cache_file;
    private string $resolved_runtime_config_file;

    /**
     * @param $root_path string Absolute application root.
     * @param $vendor_path string Absolute Composer vendor directory.
     * @param $config_directory string Absolute path or path relative to the application root.
     * @param $cache_directory string Absolute path or path relative to the application root.
     * @param $cache_filename string Compiled cache filename.
     * @param $runtime_config_file string Absolute path or path relative to the application root.
     */
    public function __construct(
        public string $root_path,
        public string $vendor_path = '',
        public string $config_directory = 'config',
        public string $cache_directory = 'runtime/cache',
        public string $cache_filename = 'config_compiled.php',
        public string $runtime_config_file = 'storage/settings/platform-runtime.php',
    ) {
        $this->resolved_config_directory = $this->absolutePath($this->config_directory);
        $this->resolved_cache_directory = $this->absolutePath($this->cache_directory);
        $this->resolved_cache_file = $this->resolved_cache_directory . '/' . $this->cache_filename;
        $this->resolved_runtime_config_file = $this->absolutePath($this->runtime_config_file);
    }

    /**
     * Returns the absolute configuration directory.
     *
     * @return string
     */
    public function configDirectory(): string
    {
        return $this->resolved_config_directory;
    }

    /**
     * Returns the absolute cache directory.
     *
     * @return string
     */
    public function cacheDirectory(): string
    {
        return $this->resolved_cache_directory;
    }

    /**
     * Returns the absolute compiled configuration cache file.
     *
     * @return string
     */
    public function cacheFile(): string
    {
        return $this->resolved_cache_file;
    }

    /**
     * Returns the absolute runtime configuration file.
     *
     * @return string
     */
    public function runtimeConfigFile(): string
    {
        return $this->resolved_runtime_config_file;
    }

    /**
     * Resolves a relative location against the application root.
     *
     * @param $path string Absolute or root-relative path.
     *
     * @return string
     */
    private function absolutePath(string $path): string
    {
        if (
            $path === ''
            || $path[0] === '/'
            || (isset($path[1], $path[2]) && $path[1] === ':' && ($path[2] === '/' || $path[2] === '\\'))
        ) {
            return $path;
        }

        return \rtrim($this->root_path, '/\\') . '/' . \ltrim($path, '/\\');
    }
}
