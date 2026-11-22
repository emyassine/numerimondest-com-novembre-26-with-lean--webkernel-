<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Config;

use Random\RandomException;
use RuntimeException;
use Throwable;
use Webkernel\ChangeWatcher\SourceSnapshot;
use Webkernel\ServiceProvider\Deployment\DeploymentLock;
use Webkernel\Config\Compiler\ConfigCompiler;
use Webkernel\Config\ConfigPaths;
use Webkernel\Config\Discovery\ConfigDiscovery;
use Webkernel\Config\Discovery\PublishableConfig;
use Webkernel\Config\Exceptions\ConfigGuardException;
use Webkernel\Config\Guards\ConfigGuard;
use Webkernel\Config\Repository\ConfigRepository;
use Webkernel\Config\Writer\ConfigWriter;

/**
 * Static façade for compiled configuration access.
 *
 * Exposes direct flat dictionary access with
 * automatic recompilation when source configuration files or directories change.
 */
final class Config
{
    /** @var array<string, mixed> Direct static memory array for sub-microsecond O(1) lookups */
    public static array $items = [];

    /** @var array<string, mixed> Configuration tree loaded alongside the flat dictionary. */
    protected static array $tree = [];

    protected static ?ConfigRepository $repository = null;
    protected static ?ConfigGuard $guard = null;
    protected static ?ConfigDiscovery $discovery = null;
    protected static ?ConfigCompiler $compiler = null;

    protected static bool $booted = false;
    protected static bool $freshness_check_scheduled = false;
    protected static ?ConfigPaths $paths = null;

    /** @var array{directories: list<string>, providers: list<class-string>, declarations_path: string, runtime_file: string}|null */
    protected static ?array $cache_inputs = null;

    /** @var array<string, array{int, int, int, int, int}|null>|null */
    protected static ?array $source_snapshot = null;

    /** @var list<string> Additional custom configuration directories */
    protected static array $custom_directories = [];

    /** @var list<class-string> Explicit provider registrations retained across reloads. */
    protected static array $providers = [];

    /** @var array<string, array{tree: array<string, mixed>}> Sections loaded on demand. */
    protected static array $loaded_sections = [];

    // Pure static façade
    private function __construct() {}

    // -------------------------------------------------------------------------
    // Bootstrap & Configuration
    // -------------------------------------------------------------------------

    /**
     * Explicitly sets system paths prior to initialization.
     *
     * @param $root_path string Absolute path to application/platform root.
     * @param $vendor_path string Absolute path to Composer vendor directory.
     * @param $cache_dir string Absolute path to cache directory.
     *
     * @return void
     */
    public static function setPaths(string $root_path, string $vendor_path = '', string $cache_dir = ''): void
    {
        $current = self::paths();

        self::configurePaths(new ConfigPaths(
            \rtrim($root_path, '/\\'),
            $vendor_path !== '' ? \rtrim($vendor_path, '/\\') : $current->vendor_path,
            $current->config_directory,
            $cache_dir !== '' ? \rtrim($cache_dir, '/\\') : $current->cache_directory,
            $current->cache_filename,
            $current->runtime_config_file,
        ));
    }

    /**
     * Replaces configuration paths before the next configuration boot.
     *
     * @param $paths ConfigPaths Resolved or overridable application paths.
     *
     * @return void
     */
    public static function configurePaths(ConfigPaths $paths): void
    {
        self::$paths = $paths;
        $GLOBALS['WEBKERNEL_CONFIG_PATH'] = $paths->configDirectory();
        self::flush();
    }

    /**
     * Registers an additional directory to scan for configuration files.
     *
     * @param $directory string
     *
     * @return void
     */
    public static function addDirectory(string $directory): void
    {
        $normalized = \rtrim($directory, '/\\');

        if ($normalized !== '' && ! \in_array($normalized, self::$custom_directories, true)) {
            self::$custom_directories[] = $normalized;
            self::flush();
        }
    }

    /**
     * Registers a ServiceProvider class for package configuration extraction.
     *
     * @param $provider_class class-string
     *
     * @return void
     */
    public static function registerProvider(string $provider_class): void
    {
        if (! \in_array($provider_class, self::$providers, true)) {
            self::$providers[] = $provider_class;
            self::flush();
        }
    }

    /**
     * Protects specified keys or prefixes from runtime modification.
     *
     * @param $protected_keys list<string>
     *
     * @return void
     */
    public static function protect(array $protected_keys): void
    {
        self::$guard = self::$guard !== null
            ? self::$guard->withKeys($protected_keys)
            : new ConfigGuard($protected_keys);

        self::$repository?->setGuard(self::$guard);
    }

    /**
     * Loads a compiled cache immediately and refreshes stale sources after a FastCGI response.
     *
     * Compiles configuration sources synchronously when the compiled cache is absent or unusable.
     *
     * @param $root_path string|null
     * @param $vendor_path string|null
     * @param $cache_dir string|null
     *
     * @return void
     */
    public static function boot(
        ?string $root_path = null,
        ?string $vendor_path = null,
        ?string $cache_dir = null,
    ): void {
        $current_paths = self::paths();
        $effective_root = $root_path !== null ? \rtrim($root_path, '/\\') : $current_paths->root_path;
        $deployment_directory = DeploymentLock::artifactDirectory($effective_root, 'config');
        $deployment_locked = $deployment_directory !== null;
        if ($deployment_locked) {
            $cache_dir = $deployment_directory;
            $site_manifest_path = $deployment_directory.'/site_configuration.php';
            if (is_file($site_manifest_path)) {
                $site_manifest = require $site_manifest_path;
                if (is_array($site_manifest)) {
                    foreach (($site_manifest['configs'] ?? []) as $config_file) {
                        if (is_string($config_file) && is_file($config_file)) {
                            self::addDirectory(dirname($config_file));
                        }
                    }
                }
            }
        }

        if (self::bootedForPaths($root_path, $vendor_path, $cache_dir)) {
            if ($deployment_locked) {
                return;
            }
            if (\PHP_SAPI === 'fpm-fcgi' && \function_exists('fastcgi_finish_request')) {
                self::scheduleFreshnessCheck();

                return;
            }

            if (self::$source_snapshot !== null && SourceSnapshot::metadataIsCurrent(self::$source_snapshot)) {
                return;
            }
        }

        if ($root_path !== null || $vendor_path !== null || $cache_dir !== null) {
            $current = self::paths();
            self::configurePaths(new ConfigPaths(
                $root_path !== null ? \rtrim($root_path, '/\\') : $current->root_path,
                $vendor_path !== null ? \rtrim($vendor_path, '/\\') : $current->vendor_path,
                $current->config_directory,
                $cache_dir !== null ? \rtrim($cache_dir, '/\\') : $current->cache_directory,
                $current->cache_filename,
                $current->runtime_config_file,
            ));
        }

        $paths = self::paths();
        $cache_file = $paths->cacheFile();
        $cache_key = self::cacheKey();
        $apcu_hit = false;
        $cached = null;

        if (\function_exists('apcu_fetch') && \function_exists('apcu_enabled') && \apcu_enabled()) {
            $cached = @\apcu_fetch($cache_key, $apcu_hit);
            if (! $apcu_hit || ! \is_array($cached) || ! self::isUsableCache($cached)) {
                $cached = null;
                $apcu_hit = false;
            }
        }

        if (! $apcu_hit) {
            \clearstatcache(true, $cache_file);
            if (\is_file($cache_file)) {
                $cached = require $cache_file;
                if (\is_array($cached) && self::isUsableCache($cached)) {
                    self::storeApcuCache($cached);
                }
            }
        }

        if (\is_array($cached) && self::isUsableCache($cached)) {
            self::$items = $cached['flat'];
            self::$tree = $cached['tree'];
            self::$source_snapshot = $cached['metadata']['snapshot'];
            self::$repository = null;
            self::$booted = true;
            self::$loaded_sections = [];
            if (! $deployment_locked) {
                self::writeSectionCachesIfMissing($cached['tree'], $cached['metadata']);
            }

            if ($deployment_locked) {
                return;
            }

            if (\PHP_SAPI === 'fpm-fcgi' && \function_exists('fastcgi_finish_request')) {
                self::scheduleFreshnessCheck();

                return;
            }

            if (SourceSnapshot::metadataIsCurrent($cached['metadata']['snapshot'])) {
                return;
            }
        }

        if ($deployment_locked) {
            // A stale config generation may also have stale routes. Drop the
            // receipt so this request falls back to the current route sources.
            DeploymentLock::invalidate($effective_root);
            $paths = self::paths();
            self::configurePaths(new ConfigPaths(
                $effective_root,
                $paths->vendor_path,
                $paths->config_directory,
                $effective_root.'/runtime/cache',
                $paths->cache_filename,
                $paths->runtime_config_file,
            ));
            self::recompile();

            return;
        }

        self::recompile();
    }

    /**
     * Forces recompilation of all configuration sources and reloads cache.
     *
     * @return void
     */
    public static function recompile(): void
    {
        self::initPaths();
        $sources = self::ensureDiscovery()->discover();
        self::doCompile($sources);
        self::$booted = true;
    }

    /**
     * Invalidates on-disk cache files and empties in-memory items.
     *
     * @return void
     */
    public static function invalidate(): void
    {
        self::initPaths();
        self::forgetApcuCache();

        $cache_file = self::paths()->cacheFile();
        if (\is_file($cache_file)) {
            @\unlink($cache_file);
        }

        self::flush();
    }

    /**
     * Flushes in-memory state. Used between requests in persistent runtimes (Swoole, FrankenPHP).
     *
     * @return void
     */
    public static function flush(): void
    {
        self::$items = [];
        self::$tree = [];
        self::$repository?->flush();
        self::$repository = null;
        self::$discovery = null;
        self::$compiler = null;
        self::$booted = false;
        self::$freshness_check_scheduled = false;
        self::$loaded_sections = [];
        self::$cache_inputs = null;
        self::$source_snapshot = null;
    }

    private static function bootedForPaths(
        ?string $root_path,
        ?string $vendor_path,
        ?string $cache_dir,
    ): bool {
        if (! self::$booted || self::$paths === null) {
            return false;
        }

        return ($root_path === null || \rtrim($root_path, '/\\') === self::$paths->root_path)
            && ($vendor_path === null || \rtrim($vendor_path, '/\\') === self::$paths->vendor_path)
            && ($cache_dir === null || \rtrim($cache_dir, '/\\') === self::$paths->cacheDirectory());
    }

    /**
     * Determines whether the configuration system has completed initial boot.
     *
     * @return bool
     */
    public static function isBooted(): bool
    {
        return self::$booted;
    }

    /**
     * Sends the current FastCGI response, then refreshes stale configuration for the next request.
     *
     * @param $snapshot array<string, array{int, int, int, int, int}|null> Snapshot loaded by this request.
     * @return void
     */
    private static function scheduleFreshnessCheck(): void
    {
        if (self::$freshness_check_scheduled) {
            return;
        }

        self::$freshness_check_scheduled = true;
        \register_shutdown_function(static function (): void {
            if (\function_exists('fastcgi_finish_request')) {
                \fastcgi_finish_request();
            }

            $lock = null;
            try {
                $lock = @\fopen(self::paths()->cacheFile() . '.refresh.lock', 'c');
                if ($lock === false || ! \flock($lock, LOCK_EX)) {
                    throw new RuntimeException('Unable to lock the configuration cache for refresh.');
                }

                \clearstatcache(true, self::paths()->cacheFile());
                if (\is_file(self::paths()->cacheFile())) {
                    $current = require self::paths()->cacheFile();
                    if (
                        \is_array($current)
                        && self::isUsableCache($current)
                        && SourceSnapshot::metadataIsCurrent($current['metadata']['snapshot'])
                    ) {
                        return;
                    }
                }

                self::recompile();
            } catch (Throwable $error) {
                \error_log('[webkernel/config] Background configuration refresh failed: ' . $error->getMessage());
            } finally {
                if (\is_resource($lock)) {
                    \flock($lock, LOCK_UN);
                    \fclose($lock);
                }
            }
        });
    }

    /**
     * Returns the APCu key for this compiled configuration cache.
     *
     * @return string
     */
    private static function cacheKey(): string
    {
        return 'webkernel.config.' . \hash('sha256', self::paths()->cacheFile());
    }

    /**
     * Checks the structure and source selection metadata of a compiled cache.
     *
     * @param $cached array<string, mixed> Candidate cache payload.
     * @return bool
     */
    private static function isUsableCache(array $cached): bool
    {
        return ($cached['metadata']['format'] ?? null) === 1
            && ($cached['metadata']['inputs'] ?? null) === self::cacheInputs()
            && \is_array($cached['metadata']['snapshot'] ?? null)
            && \is_array($cached['flat'] ?? null)
            && \is_array($cached['tree'] ?? null);
    }

    /**
     * Stores the compiled arrays in APCu when the extension is available.
     *
     * @param $cached array<string, mixed> Compiled cache payload.
     * @return void
     */
    private static function storeApcuCache(array $cached): void
    {
        if (\function_exists('apcu_store')) {
            @\apcu_store(self::cacheKey(), $cached);
        }
    }

    /**
     * Removes the shared compiled cache after an explicit runtime mutation.
     *
     * @return void
     */
    private static function forgetApcuCache(): void
    {
        if (\function_exists('apcu_delete')) {
            @\apcu_delete(self::cacheKey());
        }
    }

    /**
     * Loads one compiled configuration branch without constructing the complete dictionary.
     *
     * @param $section string Top-level configuration section.
     * @return bool True when a valid section cache was loaded.
     */
    private static function loadSection(string $section): bool
    {
        if (self::$booted) {
            return false;
        }

        if ($section === '') {
            self::boot();
            return false;
        }

        if (\array_key_exists($section, self::$loaded_sections)) {
            return true;
        }

        $paths = self::paths();
        $section_file = $paths->cacheDirectory() . '/sections/' . \hash('sha256', $section) . '.cache';
        $use_shared_cache = self::canUseSharedSectionCache();
        $section_signature = $use_shared_cache ? self::sectionFileSignature($section_file) : null;
        $apcu_key = $use_shared_cache ? self::sectionCacheKey($section_file) : '';

        if ($use_shared_cache && $section_signature !== null) {
            $apcu_hit = false;
            /** @disregard */
            $shared_cache = @\apcu_fetch($apcu_key, $apcu_hit);
            if (
                $apcu_hit
                && \is_array($shared_cache)
                && ($shared_cache['signature'] ?? null) === $section_signature
                && \is_array($shared_cache['tree'] ?? null)
            ) {
                self::$loaded_sections[$section] = ['tree' => $shared_cache['tree']];
                self::scheduleFreshnessCheck();

                return true;
            }
        }

        $contents = @\file_get_contents($section_file);
        if (! \is_string($contents)) {
            self::boot();
            return false;
        }

        try {
            $cached = @\unserialize($contents, ['allowed_classes' => true]);
        } catch (Throwable) {
            self::boot();
            return false;
        }
        if (
            ! \is_array($cached)
            || ! \array_key_exists($section, $cached)
            || ! \is_array($cached[$section])
        ) {
            self::boot();
            return false;
        }

        if (
            $use_shared_cache
            && $section_signature !== null
            && self::sectionFileSignature($section_file) === $section_signature
        ) {
            /** @disregard */
            @\apcu_store($apcu_key, [
                'signature' => $section_signature,
                'tree' => $cached,
            ], 300);
        }

        self::$loaded_sections[$section] = ['tree' => $cached];
        self::scheduleFreshnessCheck();

        return true;
    }

    /**
     * Determines whether this runtime can share decoded sections across requests.
     *
     * @return bool
     */
    private static function canUseSharedSectionCache(): bool
    {
        return PHP_SAPI !== 'cli'
            && \function_exists('apcu_enabled')
            && \apcu_enabled()
            && \function_exists('apcu_fetch')
            && \function_exists('apcu_store');
    }

    /**
     * Returns the APCu key for a compiled section cache file.
     *
     * @param $section_file string Absolute section cache path.
     * @return string
     */
    private static function sectionCacheKey(string $section_file): string
    {
        return 'webkernel.config.section.' . \hash('sha256', \serialize([
            $section_file,
            self::cacheInputs(),
        ]));
    }

    /**
     * Captures a section cache file identity without reading its contents.
     *
     * @param $section_file string Absolute section cache path.
     * @return array{int, int, int, int, int}|null
     */
    private static function sectionFileSignature(string $section_file): ?array
    {
        \clearstatcache(true, $section_file);
        $stat = @\stat($section_file);
        if ($stat === false || ! \is_file($section_file)) {
            return null;
        }

        return [
            (int) $stat['mtime'],
            (int) $stat['size'],
            (int) $stat['ctime'],
            (int) $stat['ino'],
            (int) $stat['mode'],
        ];
    }

    /**
     * Writes compact, atomically replaced cache files for each top-level section.
     *
     * @param $tree array<string, mixed> Full compiled configuration tree.
     * @param $metadata array<string, mixed> Input paths and source snapshot.
     * @return void
     *
     * @throws RandomException
     * @throws RuntimeException
     */
    private static function writeSectionCaches(array $tree, array $metadata): void
    {
        $directory = self::paths()->cacheDirectory() . '/sections';
        if (! \is_dir($directory) && ! @\mkdir($directory, 0775, true) && ! \is_dir($directory)) {
            throw new RuntimeException('Unable to create configuration section cache directory: ' . $directory);
        }

        $current_files = [];
        $use_shared_cache = self::canUseSharedSectionCache();
        foreach ($tree as $section => $section_values) {
            $section = (string) $section;
            $section_tree = [$section => $section_values];
            $section_file = $directory . '/' . \hash('sha256', $section) . '.cache';
            $current_files[$section_file] = true;
            $contents = \serialize($section_tree);
            $temporary_file = $section_file . '.' . \bin2hex(\random_bytes(4)) . '.tmp';
            if (@\file_put_contents($temporary_file, $contents, LOCK_EX) === false) {
                throw new RuntimeException('Unable to write configuration section cache: ' . $temporary_file);
            }
            if (! @\rename($temporary_file, $section_file)) {
                @\unlink($temporary_file);
                throw new RuntimeException('Unable to replace configuration section cache: ' . $section_file);
            }
            if ($use_shared_cache && \function_exists('apcu_delete')) {
                @\apcu_delete(self::sectionCacheKey($section_file));
            }
        }

        foreach (\glob($directory . '/*') ?: [] as $old_file) {
            if (
                ! isset($current_files[$old_file])
                && \preg_match('/^[a-f0-9]{64}\.cache$/D', \basename($old_file)) === 1
            ) {
                if ($use_shared_cache && \function_exists('apcu_delete')) {
                    @\apcu_delete(self::sectionCacheKey($old_file));
                }
                @\unlink($old_file);
            }
        }
    }

    /**
     * Rebuilds section caches when a full cache predates section-based lookup.
     *
     * @param $tree array<string, mixed> Compiled configuration tree.
     * @param $metadata array<string, mixed> Cache metadata.
     * @return void
     */
    private static function writeSectionCachesIfMissing(array $tree, array $metadata): void
    {
        $directory = self::paths()->cacheDirectory() . '/sections';
        foreach (\array_keys($tree) as $section) {
            $section_file = $directory . '/' . \hash('sha256', (string) $section) . '.cache';
            if (! \is_file($section_file)) {
                self::writeSectionCaches($tree, $metadata);
                return;
            }
        }
    }

    // -------------------------------------------------------------------------
    // Value Access & Mutation
    // -------------------------------------------------------------------------

    /**
     * Retrieves a configuration value by dot-notation key.
     *
     * @param $key string Dot-notation configuration key.
     * @param $default mixed Fallback value when key is absent.
     *
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$items[$key] ?? self::resolveMissing($key, $default);
    }

    /**
     * Determines whether a configuration key exists, including a null value.
     *
     * @param $key string Dot-notation configuration key.
     *
     * @return bool
     */
    public static function has(string $key): bool
    {
        return isset(self::$items[$key]) || self::resolveHas($key);
    }

    /**
     * Sets a configuration value at runtime and persists it atomically.
     *
     * @param $key string Dot-notation configuration key.
     * @param $value mixed Value to assign.
     *
     * @return ConfigRepository Fluent repository instance.
     *
     * @throws ConfigGuardException When key is protected.
     */
    public static function set(string $key, mixed $value): ConfigRepository
    {
        if (! self::$booted) {
            self::boot();
        }

        $repository = self::repository();
        self::$guard?->assert($key);

        self::persistRuntimeOverride($key, $value);
        $repository->set($key, $value);
        self::$items = $repository->allFlat();
        self::$tree = $repository->all();

        return $repository;
    }

    /**
     * Returns the entire multi-dimensional configuration tree.
     *
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        if (! self::$booted) {
            self::boot();
        }

        return self::repository()->all();
    }

    /**
     * Returns the compiled flat dot-notation dictionary.
     *
     * @return array<string, mixed>
     */
    public static function allFlat(): array
    {
        if (! self::$booted) {
            self::boot();
        }

        return self::$items;
    }

    /**
     * Returns all publishable package configurations, optionally filtered by tag.
     *
     * @param $tag string|null
     *
     * @return list<PublishableConfig>
     */
    public static function publishables(?string $tag = null): array
    {
        return self::ensureDiscovery()->getPublishables($tag);
    }

    /**
     * Resolves a named path from configuration into an absolute filesystem path.
     *
     * @param $key string Dot-notation key containing relative path, or raw path string.
     * @param $sub_path string Optional relative sub-segment to append.
     *
     * @return string Absolute filesystem path.
     */
    public static function path(string $key, string $sub_path = ''): string
    {
        if (! self::$booted) {
            self::boot();
        }

        $resolved = self::get($key);

        if (! \is_string($resolved) || $resolved === '') {
            $resolved = $key;
        }

        $full = \str_starts_with($resolved, '/')
            ? $resolved
            : self::paths()->root_path . '/' . \ltrim($resolved, '/\\');

        if ($sub_path !== '') {
            $full .= '/' . \ltrim($sub_path, '/\\');
        }

        return $full;
    }

    /**
     * Returns the underlying repository instance.
     *
     * @return ConfigRepository
     */
    public static function repository(): ConfigRepository
    {
        if (! self::$booted) {
            self::boot();
        }

        return self::$repository ??= new ConfigRepository(self::$tree, self::$items, self::$guard);
    }

    /**
     * Alias of repository() for compatibility.
     *
     * @return ConfigRepository
     */
    public static function instance(): ConfigRepository
    {
        return self::repository();
    }

    // -------------------------------------------------------------------------
    // Internal Helpers
    // -------------------------------------------------------------------------

    /**
     * Resolves a key that has not yet been memoized.
     *
     * @param $key string
     * @param $default mixed
     *
     * @return mixed
     */
    protected static function resolveMissing(string $key, mixed $default): mixed
    {
        if (\array_key_exists($key, self::$items)) {
            return self::$items[$key];
        }

        if (! self::$booted) {
            $separator = \strpos($key, '.');
            $section = $separator === false ? $key : \substr($key, 0, $separator);
            if (! isset(self::$loaded_sections[$section])) {
                self::loadSection($section);
            }
            if (isset(self::$loaded_sections[$section])) {
                return self::getSectionValue($section, $key, $default);
            }

            if (! self::$booted) {
                self::boot();
            }

            if (isset(self::$items[$key])) {
                return self::$items[$key];
            }
        }

        if (\array_key_exists($key, self::$items)) {
            return self::$items[$key];
        }

        return $default;
    }

    /**
     * Fallback check when key evaluates to false in isset(self::$items[$key]).
     *
     * @param $key string
     *
     * @return bool
     */
    protected static function resolveHas(string $key): bool
    {
        if (\array_key_exists($key, self::$items)) {
            return true;
        }

        if (! self::$booted) {
            $separator = \strpos($key, '.');
            $section = $separator === false ? $key : \substr($key, 0, $separator);
            if (! isset(self::$loaded_sections[$section])) {
                self::loadSection($section);
            }
            if (isset(self::$loaded_sections[$section])) {
                return self::hasSectionValue($section, $key);
            }

            if (! self::$booted) {
                self::boot();
            }
        }

        return \array_key_exists($key, self::$items);
    }

    /**
     * Reads a dot-notation key from one loaded nested section.
     *
     * @param $section string Top-level configuration section.
     * @param $key string Dot-notation key.
     * @param $default mixed Fallback value.
     * @return mixed
     */
    private static function getSectionValue(string $section, string $key, mixed $default): mixed
    {
        $value = self::$loaded_sections[$section]['tree'];
        foreach (\explode('.', $key) as $part) {
            if (! \is_array($value) || ! \array_key_exists($part, $value)) {
                return $default;
            }

            $value = $value[$part];
        }

        self::$items[$key] = $value;

        return $value;
    }

    /**
     * Checks a dot-notation key inside one loaded nested section.
     *
     * @param $section string Top-level configuration section.
     * @param $key string Dot-notation key.
     * @return bool
     */
    private static function hasSectionValue(string $section, string $key): bool
    {
        $value = self::$loaded_sections[$section]['tree'];
        foreach (\explode('.', $key) as $part) {
            if (! \is_array($value) || ! \array_key_exists($part, $value)) {
                return false;
            }

            $value = $value[$part];
        }

        self::$items[$key] = $value;

        return true;
    }

    /**
     * Initializes default platform, vendor, cache, and runtime paths.
     *
     * @return void
     */
    protected static function initPaths(): void
    {
        $paths = self::paths();
        if ($paths->vendor_path === '') {
            self::$paths = new ConfigPaths(
                $paths->root_path,
                \vendor_path(),
                $paths->config_directory,
                $paths->cache_directory,
                $paths->cache_filename,
                $paths->runtime_config_file,
            );
        }

        $cache_directory = self::paths()->cacheDirectory();

        if (! \is_dir($cache_directory) && ! @\mkdir($cache_directory, 0775, true) && ! \is_dir($cache_directory)) {
            throw new RuntimeException('Unable to create runtime cache directory: ' . $cache_directory);
        }
    }

    /**
     * Returns the configured paths or creates the default path set.
     *
     * @return ConfigPaths
     */
    protected static function paths(): ConfigPaths
    {
        if (self::$paths === null) {
            self::$paths = new ConfigPaths(\base_path());
        }

        return self::$paths;
    }

    /**
     * Ensures the ConfigDiscovery service is instantiated.
     *
     * @return ConfigDiscovery
     */
    protected static function ensureDiscovery(): ConfigDiscovery
    {
        if (self::$discovery !== null) {
            return self::$discovery;
        }

        self::initPaths();

        $dirs = [
            ...self::$custom_directories,
            self::paths()->configDirectory(),
        ];

        // Site runtime configuration is declared explicitly through its config directories.
        // Do not merge stale Composer provider manifests from an older deployment.
        $discovery = new ConfigDiscovery($dirs);

        foreach (self::$providers as $provider_class) {
            $discovery->addProvider($provider_class);
        }

        self::$discovery = $discovery;

        return $discovery;
    }

    /**
     * Identifies the inputs that select configuration sources and precedence.
     *
     * @return array{directories: list<string>, providers: list<class-string>, declarations_path: string, runtime_file: string}
     */
    protected static function cacheInputs(): array
    {
        if (self::$cache_inputs !== null) {
            return self::$cache_inputs;
        }

        $paths = self::paths();

        return self::$cache_inputs = [
            'directories' => [...self::$custom_directories, $paths->configDirectory()],
            'providers' => self::$providers,
            'declarations_path' => '',
            'runtime_file' => $paths->runtimeConfigFile(),
        ];
    }

    /**
     * Ensures the ConfigCompiler service is instantiated.
     *
     * @return ConfigCompiler
     */
    protected static function ensureCompiler(): ConfigCompiler
    {
        return self::$compiler ??= new ConfigCompiler();
    }

    /**
     * Performs compilation, writes cache, and updates in-memory structures.
     *
     * @param $sources array<string, array{path: string, type: string}>
     *
     * @return void
     */
    protected static function doCompile(array $sources): void
    {
        $file_sources = [];
        foreach ($sources as $stem => $entry) {
            $file_sources[$stem] = $entry['path'];
        }

        $runtime_overrides = [];
        $paths = self::paths();
        $discovery = self::ensureDiscovery();
        $compiler = self::ensureCompiler();
        $snapshot = SourceSnapshot::captureMetadataPaths([
            ...$discovery->getWatchedPaths(),
            $paths->runtimeConfigFile(),
        ]);
        self::$source_snapshot = $snapshot;

        if (\is_file($paths->runtimeConfigFile())) {
            if (\function_exists('opcache_invalidate')) {
                \opcache_invalidate($paths->runtimeConfigFile(), true);
            }
            $loaded = require $paths->runtimeConfigFile();
            if (\is_array($loaded)) {
                $runtime_overrides = $loaded;
            }
        }

        $compiled = $compiler->compile($file_sources, $runtime_overrides);

        if (SourceSnapshot::metadataIsCurrent($snapshot)) {
            $metadata = [
                'format' => 1,
                'inputs' => self::cacheInputs(),
                'snapshot' => $snapshot,
            ];
            $compiler->writeCache($paths->cacheFile(), $compiled['flat'], $compiled['tree'], $metadata);
            self::writeSectionCaches($compiled['tree'], $metadata);
            self::storeApcuCache([
                'flat' => $compiled['flat'],
                'tree' => $compiled['tree'],
                'metadata' => $metadata,
            ]);
        }

        self::$items = $compiled['flat'];
        self::$tree = $compiled['tree'];
        self::$repository = null;
        self::$loaded_sections = [];
    }

    /**
     * Persists a key-value override into the platform runtime file atomically.
     *
     * @param $key string
     * @param $value mixed
     *
     * @return void
     */
    protected static function persistRuntimeOverride(string $key, mixed $value): void
    {
        $current = [];
        $runtime_config_file = self::paths()->runtimeConfigFile();
        if (\is_file($runtime_config_file)) {
            $loaded = require $runtime_config_file;
            if (\is_array($loaded)) {
                $current = $loaded;
            }
        }

        // Expand key into nested branch for runtime file
        $parts = \explode('.', $key);
        $tree = $value;
        for ($i = \count($parts) - 1; $i >= 0; $i--) {
            $tree = [$parts[$i] => $tree];
        }

        $merged = \array_replace_recursive($current, $tree);
        ConfigWriter::write($runtime_config_file, $merged);
        self::forgetApcuCache();
    }
}
