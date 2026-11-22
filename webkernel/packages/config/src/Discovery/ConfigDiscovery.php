<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Config\Discovery;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;
use Webkernel\Config\Discovery\PublishableConfig;
use Webkernel\ServiceProvider;

/**
 * Discovers configuration files dynamically from directories and package providers.
 *
 * Scans directories without hardcoded filenames, associates files with dot-notation stems,
 * and extracts package configurations and publishable manifests.
 */
class ConfigDiscovery
{
    /** @var list<string> */
    protected array $directories = [];

    /** @var array<string, string> Discovered files mapped as stem => absolute_path */
    protected array $file_map = [];

    /** @var array<string, PublishableConfig> */
    protected array $publishables = [];

    /** @var list<class-string> */
    protected array $providers = [];

    /** @var array<string, true> Paths checked when reusing a compiled configuration. */
    protected array $watched_paths = [];

    /**
     * @param $directories list<string> Directories to scan for PHP configuration files.
     * @param $declarations_path string Directory containing compiled Webkernel declarations.
     */
    public function __construct(
        array $directories = [],
        protected string $declarations_path = '',
    ) {
        foreach ($directories as $directory) {
            $this->addDirectory($directory);
        }
    }

    /**
     * Registers an additional directory to scan for configuration files.
     *
     * @param $directory string Directory path.
     *
     * @return static
     */
    public function addDirectory(string $directory): static
    {
        $normalized = \rtrim($directory, '/\\');

        if ($normalized !== '' && ! \in_array($normalized, $this->directories, true)) {
            $this->directories[] = $normalized;
        }

        return $this;
    }

    /**
     * Registers a ServiceProvider class for package configuration extraction.
     *
     * @param $provider_class class-string
     *
     * @return static
     */
    public function addProvider(string $provider_class): static
    {
        if (! is_subclass_of($provider_class, ServiceProvider::class)) {
            throw new \InvalidArgumentException(sprintf(
                'Configuration provider must extend %s: %s',
                ServiceProvider::class,
                $provider_class,
            ));
        }
        if (! \in_array($provider_class, $this->providers, true)) {
            $this->providers[] = $provider_class;
        }

        return $this;
    }

    /**
     * Discovers all configuration files from registered directories and providers.
     *
     * Returns an array of section definitions:
     *   [ 'stem_key' => [ 'path' => '/abs/path.php', 'type' => 'directory|package' ] ]
     *
     * @return array<string, array{path: string, type: string}>
     */
    public function discover(): array
    {
        $results = [];
        $this->file_map = [];
        $this->publishables = [];
        $this->watched_paths = \array_fill_keys($this->directories, true);

        // 1. Package provider configurations (loaded first, overridable by user configuration)
        $package_configs = $this->discoverProviderConfigs();
        foreach ($package_configs as $key => $path) {
            $this->watched_paths[$path] = true;
            $results[$key] = [
                'path' => $path,
                'type' => 'package',
            ];
        }

        // 2. Directory configurations (scans all registered directories)
        foreach ($this->directories as $directory) {
            if (! \is_dir($directory)) {
                continue;
            }

            $directory_configs = $this->scanDirectory($directory);
            foreach ($directory_configs as $stem => $path) {
                $results[$stem] = [
                    'path' => $path,
                    'type' => 'directory',
                ];
            }
        }

        foreach ($results as $stem => $entry) {
            $this->file_map[$stem] = $entry['path'];
        }

        return $results;
    }

    /**
     * Returns source file paths from the most recent discovery pass.
     *
     * @return list<string>
     */
    public function getSourceFiles(): array
    {
        return \array_values(\array_unique($this->file_map));
    }

    /**
     * Returns files and directories whose changes require a new discovery pass.
     *
     * @return list<string>
     */
    public function getWatchedPaths(): array
    {
        return \array_keys($this->watched_paths);
    }

    /**
     * Returns all registered directory paths.
     *
     * @return list<string>
     */
    public function getDirectories(): array
    {
        return $this->directories;
    }

    /**
     * Returns publishable configuration items, optionally filtered by tag.
     *
     * @param $tag string|null
     *
     * @return list<PublishableConfig>
     */
    public function getPublishables(?string $tag = null): array
    {
        // Ensure provider configurations are inspected
        $this->discoverProviderConfigs();

        $items = [];

        foreach ($this->publishables as $publishable) {
            if ($tag === null || $publishable->tag === $tag) {
                $items[] = $publishable;
            }
        }

        return $items;
    }

    /**
     * Scans a single directory recursively for PHP configuration files.
     *
     * @param $directory string
     *
     * @return array<string, string> Keyed by dot-notation stem.
     */
    protected function scanDirectory(string $directory): array
    {
        $found = [];
        $real_dir = \realpath($directory) ?: $directory;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($real_dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                $this->watched_paths[$file->getPathname()] = true;
                continue;
            }

            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $path = $file->getRealPath() ?: $file->getPathname();
            if ($path === $real_dir.'/providers.php') {
                continue;
            }
            $stem = $this->computeStem($real_dir, $path);

            $found[$stem] = $path;
            $this->watched_paths[$path] = true;
        }

        return $found;
    }

    /**
     * Computes the configuration section stem from a file path relative to its root.
     *
     * Examples:
     *   "config/app.php"              → "app"
     *   "config/telemetry.php"        → "telemetry"
     *   "config/services/stripe.php"  → "services.stripe"
     *
     * @param $root_dir string
     * @param $file_path string
     *
     * @return string
     */
    protected function computeStem(string $root_dir, string $file_path): string
    {
        $relative = \ltrim(\substr($file_path, \strlen($root_dir)), '/\\');

        if (\str_ends_with($relative, '.php')) {
            $relative = \substr($relative, 0, -4);
        }

        return \str_replace(['/', '\\'], '.', $relative);
    }

    /**
     * Extracts configuration declarations and publishable definitions from registered providers.
     *
     * @return array<string, string> Keyed by config key stem.
     */
    protected function discoverProviderConfigs(): array
    {
        $providers = $this->resolveProviders();
        $configs = [];

        foreach ($providers as $class) {
            if (! \is_string($class) || ! \class_exists($class)) {
                continue;
            }

            $provider_file = (new ReflectionClass($class))->getFileName();
            if ($provider_file !== false) {
                $this->watched_paths[$provider_file] = true;
            }

            // Extract CONFIG constant via declaration() method if available, or direct constant reflection
            $declarations = [];
            if (\method_exists($class, 'declaration')) {
                $declarations = $class::declaration('CONFIG');
            } elseif (\defined($class . '::CONFIG')) {
                $declarations = (array) \constant($class . '::CONFIG');
            }

            if (! \is_array($declarations) || $declarations === []) {
                continue;
            }

            foreach ($declarations as $key => $item) {
                // Shape A: Rich associative format ['courier' => ['path' => '...', 'publish' => '...', 'tag' => '...']]
                if (\is_string($key) && \is_array($item) && isset($item['path']) && \is_string($item['path'])) {
                    $path = realpath($item['path']) ?: $item['path'];
                    $configs[$key] = $path;

                    if (isset($item['publish']) && \is_string($item['publish'])) {
                        $this->publishables[$key] = new PublishableConfig(
                            key: $key,
                            source: $path,
                            target: $item['publish'],
                            tag: isset($item['tag']) && \is_string($item['tag']) ? $item['tag'] : null,
                            package: $class,
                        );
                    }
                    continue;
                }

                // Shape B: Simple string path ['/path/to/file.php'] or ['courier' => '/path/to/file.php']
                if (\is_string($item) && $item !== '') {
                    $item = realpath($item) ?: $item;
                    $stem = \is_string($key) ? $key : \basename($item, '.php');
                    $configs[$stem] = $item;

                    $target = \function_exists('config_path') ? \config_path($stem . '.php') : '';
                    if ($target !== '') {
                        $this->publishables[$stem] = new PublishableConfig(
                            key: $stem,
                            source: $item,
                            target: $target,
                            tag: null,
                            package: $class,
                        );
                    }
                }
            }
        }

        return $configs;
    }

    /**
     * Resolves active provider classes from explicit and compiled declarations.
     *
     * @return list<class-string>
     */
    protected function resolveProviders(): array
    {
        $classes = $this->providers;

        if ($this->declarations_path !== '') {
            $manifest = $this->declarations_path . '/webkernel_providers.php';
            $this->watched_paths[$manifest] = true;
            if (\is_file($manifest)) {
                if (\function_exists('opcache_invalidate')) {
                    \opcache_invalidate($manifest, true);
                }
                $loaded = require $manifest;
                if (\is_array($loaded)) {
                    foreach ($loaded as $class) {
                        if (\is_string($class) && ! \in_array($class, $classes, true)) {
                            $classes[] = $class;
                        }
                    }
                }
            }
        }

        return $classes;
    }
}
