<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Codebase\Compiler;

use Composer\Composer;
use Composer\Autoload\ClassLoader;
use Composer\Package\PackageInterface;
use JsonException;
use Composer\Script\Event;
use ReflectionClass;
use RuntimeException;
use Webkernel\ChangeWatcher\CacheStore;
use Webkernel\ChangeWatcher\Watcher;
use Webkernel\Getters\GetWebkernelComponentCatalog;
use Webkernel\Lifecycle\StandardLCEvent;
use Webkernel\ServiceProvider\PackageMetadata;
use Webkernel\ServiceProvider\Deployment\ArtifactBuilder;
use Webkernel\Platform\PanelProvider;
use Webkernel\ServiceProvider;

/** Publishes package declaration manifests under the application's runtime directory. */
final class CompileDeclarations extends StandardLCEvent
{
    /** @throws RuntimeException When declarations are invalid or cannot be published. */
    protected function run(): void
    {
        $composer = $this->composerEvent()->getComposer();
        $root_manifest = $composer->getConfig()->getConfigSource()->getName();
        $root_path = realpath(dirname($root_manifest));
        if ($root_path === false) {
            throw new RuntimeException('Unable to resolve Composer project root.');
        }

        $paths_config = $root_path . '/config/paths.php';
        $paths = is_file($paths_config) ? require $paths_config : [];
        $cache_path = is_array($paths) ? ($paths['cache'] ?? null) : null;
        if (! is_string($cache_path) || $cache_path === '' || self::isAbsolute($cache_path) || str_contains($cache_path, '..')) {
            throw new RuntimeException('config/paths.php must define a valid cache path.');
        }
        $cache_directory = $root_path . '/' . $cache_path;

        $vendor_path = $composer->getConfig()->get('vendor-dir');
        if (! is_string($vendor_path)) {
            throw new RuntimeException('Composer vendor directory is unavailable.');
        }
        if (! self::isAbsolute($vendor_path)) {
            $vendor_path = $root_path . '/' . $vendor_path;
        }
        $vendor_path = realpath($vendor_path);
        if ($vendor_path === false) {
            throw new RuntimeException('Unable to resolve Composer vendor directory.');
        }
        $this->registerPackageAutoloader($vendor_path);

        $registry = $this->compileRegistry($composer, $root_path, $root_manifest);
        $sources = $registry['sources'];
        unset($registry['sources']);

        $store = new CacheStore($cache_directory . '/change-watcher/declarations');
        $watcher = new Watcher($store, 'declarations-v2');
        $watcher->watch(
            id: 'webkernel:declarations',
            sources: $sources,
            compiler: static fn (array $source_paths): string => '<?php return ' . var_export($registry, true) . ';' . PHP_EOL,
            revision: 'declarations-v2',
        );
        $watcher->poll();

        $publication = $watcher->current('webkernel:declarations') ?? $store->read('webkernel:declarations');
        if ($publication === null) {
            throw new RuntimeException('The Webkernel declarations artifact was not published.');
        }

        $manifest_directory = $cache_directory . '/declarations';
        if (! is_dir($manifest_directory) && ! mkdir($manifest_directory, 0775, true) && ! is_dir($manifest_directory)) {
            throw new RuntimeException('Unable to create the Webkernel declaration directory: '.$manifest_directory);
        }

        $manifest_keys = [
            'providers',
            'bindings',
            'view_directives',
            'routes',
            'route_bootstrappers',
            'runtime_bootstrappers',
            'views',
            'components',
            'component_catalog',
            'lang',
            'commands',
            'panels',
            'panel_owners',
            'assets',
            'asset_roots',
            'vite',
            'packages',
            'build_artifacts',
        ];
        foreach ($manifest_keys as $key) {
            $this->writeManifest($manifest_directory . '/webkernel_' . $key . '.php', $registry[$key]);
        }
        foreach (['webkernel_panel_declarations.php', 'webkernel_panel_routes.php'] as $obsolete_manifest) {
            $obsolete_path = $manifest_directory . '/' . $obsolete_manifest;
            if (is_file($obsolete_path) && ! unlink($obsolete_path)) {
                throw new RuntimeException('Unable to remove obsolete panel manifest: '.$obsolete_path);
            }
        }
        $this->publishComponentCatalogPages(
            $manifest_directory,
            $registry['component_catalog_pages'],
            $registry['component_catalog_summary'],
        );

        $this->publishBootManifest($root_path, $manifest_directory, $manifest_keys, $registry['component_catalog_pages'], $registry['component_catalog_summary'], $paths);

        // Remove only Webkernel-owned artifacts after the new publication succeeds.
        $obsolete_paths = array_map(
            static fn (string $key): string => $vendor_path.'/composer/webkernel_'.$key.'.php',
            [...$manifest_keys, 'component_catalog_summary', 'panel_declarations', 'panel_routes'],
        );
        $obsolete_paths = [...$obsolete_paths, ...(glob($vendor_path.'/composer/webkernel_component_catalog_*_page_*.php') ?: [])];
        foreach ($obsolete_paths as $obsolete_path) {
            if (is_file($obsolete_path) && ! unlink($obsolete_path)) {
                throw new RuntimeException('Unable to remove obsolete Webkernel manifest: '.$obsolete_path);
            }
            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($obsolete_path, true);
            }
        }
    }

    /** Registers Composer's generated class map for dump-time provider reflection. */
    private function registerPackageAutoloader(string $vendor_path): void
    {
        $psr4_file = $vendor_path . '/composer/autoload_psr4.php';
        $classmap_file = $vendor_path . '/composer/autoload_classmap.php';
        if (! is_file($psr4_file) || ! is_file($classmap_file)) {
            throw new RuntimeException('Composer generated class metadata is unavailable.');
        }

        $psr4 = require $psr4_file;
        $classmap = require $classmap_file;
        if (! is_array($psr4) || ! is_array($classmap)) {
            throw new RuntimeException('Composer generated class metadata is invalid.');
        }

        $loader = new ClassLoader($vendor_path);
        foreach ($psr4 as $prefix => $paths) {
            if (is_string($prefix) && is_array($paths)) {
                $loader->setPsr4($prefix, $paths);
            }
        }
        $loader->addClassMap($classmap);
        $loader->register(true);
    }

    /**
     * Collects declarations from root and installed packages without executing providers.
     *
     * @param $composer Composer Active Composer instance.
     * @param $root_path string Absolute project path.
     * @param $root_manifest string Root Composer manifest path.
     * @return array<string, mixed>
     */
    private function compileRegistry(Composer $composer, string $root_path, string $root_manifest): array
    {
        $packages = array_merge(
            [$composer->getPackage()],
            $composer->getRepositoryManager()->getLocalRepository()->getPackages(),
        );
        $runtime_packages = [$composer->getPackage()->getName() => true];
        $lock_path = $root_path.'/composer.lock';
        if (is_file($lock_path)) {
            $lock = json_decode((string) file_get_contents($lock_path), true);
            foreach (is_array($lock['packages'] ?? null) ? $lock['packages'] : [] as $locked_package) {
                if (is_array($locked_package) && is_string($locked_package['name'] ?? null)) {
                    $runtime_packages[$locked_package['name']] = true;
                }
            }
        }
        $packages = array_values(array_filter(
            $packages,
            static fn (PackageInterface $package): bool => isset($runtime_packages[$package->getName()]),
        ));
        $registry = [
            'providers' => [],
            'bindings' => [],
            'routes' => [],
            'route_bootstrappers' => [],
            'runtime_bootstrappers' => [],
            'views' => [],
            'view_directives' => [],
            'components' => [],
            'component_catalog' => [],
            'component_catalog_pages' => [],
            'component_catalog_summary' => [],
            'lang' => [],
            'commands' => [],
            'panels' => [],
            'panel_owners' => [],
            'assets' => [],
            'asset_roots' => [],
            'vite' => [],
            'packages' => [],
            'build_artifacts' => [],
        ];
        $sources = [$this->absoluteSource($root_manifest)];
        $seen_packages = [];
        $seen_providers = [];
        $package_contexts = [];
        $provider_declarations = [];

        foreach ($packages as $package) {
            if (! $package instanceof PackageInterface || isset($seen_packages[$package->getName()])) {
                continue;
            }
            $seen_packages[$package->getName()] = true;

            $package_manifest = $this->packageManifestPath($composer, $package, $root_path, $root_manifest);
            $manifest_contents = file_get_contents($package_manifest);
            if (! is_string($manifest_contents)) {
                throw new RuntimeException('Unable to read package manifest: ' . $package_manifest);
            }
            try {
                $manifest_data = json_decode($manifest_contents, true, flags: JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new RuntimeException('Invalid package manifest: ' . $package_manifest, previous: $exception);
            }
            $manifest_extra = is_array($manifest_data) ? ($manifest_data['extra']['webkernel'] ?? null) : null;
            $extra = is_array($manifest_extra) ? $manifest_extra : ($package->getExtra()['webkernel'] ?? []);
            $metadata = [];
            $declaration_class = is_array($extra) ? ($extra['package-declaration'] ?? null) : null;
            if (is_string($declaration_class)) {
                if (! class_exists($declaration_class) || ! is_subclass_of($declaration_class, PackageMetadata::class)) {
                    throw new RuntimeException(sprintf(
                        'Package "%s" declaration must extend %s: %s',
                        $package->getName(),
                        PackageMetadata::class,
                        $declaration_class,
                    ));
                }
                $metadata = $declaration_class::metadata();
                $declaration_file = (new ReflectionClass($declaration_class))->getFileName();
                if (! is_string($declaration_file)) {
                    throw new RuntimeException(sprintf('Package declaration has no source file: %s', $declaration_class));
                }
                $sources[] = $this->absoluteSource($declaration_file);
            }
            $catalog = $metadata['catalog'] ?? null;
            if (is_array($catalog) && $catalog !== []) {
                $registry['packages'][$package->getName()] = [
                    'name' => $package->getName(),
                    'version' => $package->getPrettyVersion(),
                    'catalog' => $catalog,
                ];
            }
            $artifact_builders = $metadata['artifacts'] ?? [];
            if (! is_array($artifact_builders) || ! array_is_list($artifact_builders)) {
                throw new RuntimeException(sprintf('Package "%s" artifacts must be a list of builder classes.', $package->getName()));
            }
            foreach ($artifact_builders as $artifact_builder) {
                if (! is_string($artifact_builder) || ! class_exists($artifact_builder) || ! is_subclass_of($artifact_builder, ArtifactBuilder::class)) {
                    throw new RuntimeException(sprintf('Package "%s" declares an invalid build artifact class.', $package->getName()));
                }
                $builder_file = (new ReflectionClass($artifact_builder))->getFileName();
                if (! is_string($builder_file)) {
                    throw new RuntimeException(sprintf('Build artifact class has no source file: %s', $artifact_builder));
                }
                $registry['build_artifacts'][] = $artifact_builder;
                $sources[] = $this->absoluteSource($builder_file);
            }
            $sources[] = $package_manifest;
            $package_contexts[$package->getName()] = [
                'manifest' => $package_manifest,
                'prefix' => $this->prefix($metadata['prefix'] ?? null, $package->getName(), $package === $composer->getPackage()),
                // Composer's installed package type may be rewritten by an installer
                // (for example, system-panel is installed below modules/ but is still
                // a library, not a platform module).
                'type' => is_string($manifest_data['type'] ?? null) ? $manifest_data['type'] : $package->getType(),
            ];
            if (is_array($extra) && array_key_exists('provider', $extra)) {
                $provider_declarations[] = [
                    'class' => $extra['provider'],
                    'package' => $package->getName(),
                    'source' => $package_manifest,
                ];
            }
        }

        $providers_path = $root_path.'/config/providers.php';
        if (is_file($providers_path)) {
            $sources[] = $this->absoluteSource($providers_path);
            $configured_providers = (static fn (string $path): mixed => require $path)($providers_path);
            if (! is_array($configured_providers) || ! array_is_list($configured_providers)) {
                throw new RuntimeException('config/providers.php must return a list of ServiceProvider class names.');
            }
            foreach ($configured_providers as $provider_class) {
                $provider_declarations[] = [
                    'class' => $provider_class,
                    'package' => null,
                    'source' => $providers_path,
                ];
            }
        } elseif (file_exists($providers_path)) {
            throw new RuntimeException('config/providers.php must be a readable PHP file.');
        } elseif (is_dir($root_path.'/config')) {
            $sources[] = $this->absoluteSource($root_path.'/config');
        }

        foreach ($provider_declarations as $declaration) {
            $provider_class = $declaration['class'];
            if (! is_string($provider_class) || ! class_exists($provider_class) || ! is_subclass_of($provider_class, ServiceProvider::class)) {
                throw new RuntimeException(sprintf(
                    'Provider declared by %s must extend %s: %s',
                    $declaration['source'],
                    ServiceProvider::class,
                    is_string($provider_class) ? $provider_class : get_debug_type($provider_class),
                ));
            }

            $reflection = new ReflectionClass($provider_class);
            $provider_file = $reflection->getFileName();
            if (! is_string($provider_file)) {
                throw new RuntimeException(sprintf('Provider has no source file: %s', $provider_class));
            }
            $provider_class = $reflection->getName();
            if (isset($seen_providers[$provider_class])) {
                continue;
            }
            $seen_providers[$provider_class] = true;
            $registry['providers'][] = $provider_class;
            $sources[] = $this->absoluteSource($provider_file);

            $package_name = $declaration['package'];
            if ($package_name === null) {
                $package_name = $composer->getPackage()->getName();
                $owner_length = 0;
                foreach ($package_contexts as $candidate_name => $candidate) {
                    $candidate_root = dirname($candidate['manifest']).'/';
                    if (str_starts_with($provider_file, $candidate_root) && strlen($candidate_root) > $owner_length) {
                        $package_name = $candidate_name;
                        $owner_length = strlen($candidate_root);
                    }
                }
            }
            $context = $package_contexts[$package_name];

            $this->appendBindings($registry['bindings'], $provider_class::declaration('BINDINGS'));
            $this->appendViewDirectives($registry['view_directives'], $provider_class::declaration('VIEW_DIRECTIVES'), $provider_class);
            $prefix = $context['prefix'];
            $this->appendFiles($registry['routes'], $provider_class::declaration('ROUTES'), $sources);
            $this->appendCallables($registry['route_bootstrappers'], $provider_class::declaration('ROUTE_BOOTSTRAPPERS'), 'route bootstrapper');
            $this->appendCallables($registry['runtime_bootstrappers'], $provider_class::declaration('RUNTIME_BOOTSTRAPPERS'), 'runtime bootstrapper');
            $views = $provider_class::declaration('VIEWS');
            if (array_is_list($views)) {
                $this->appendDirectories($registry['views'], $prefix, $views, $sources);
            } else {
                foreach ($views as $view_namespace => $directories) {
                    if (! is_string($view_namespace) || preg_match('/\\A[a-zA-Z0-9_-]+\\z/', $view_namespace) !== 1 || ! is_array($directories)) {
                        throw new RuntimeException('View namespaces must map valid namespace names to directory lists.');
                    }
                    $this->appendDirectories($registry['views'], $view_namespace, $directories, $sources);
                }
            }
            $components = $provider_class::declaration('COMPONENTS');
            if (array_is_list($components)) {
                $this->appendDirectories($registry['components'], $prefix, $components, $sources);
            } else {
                foreach ($components as $component_namespace => $directories) {
                    if (! is_string($component_namespace) || preg_match('/\\A[a-zA-Z0-9_-]+\\z/', $component_namespace) !== 1 || ! is_array($directories)) {
                        throw new RuntimeException('Component namespaces must map valid namespace names to directory lists.');
                    }
                    $this->appendDirectories($registry['components'], $component_namespace, $directories, $sources);
                }
            }
            $this->appendDirectories($registry['lang'], $prefix, $provider_class::declaration('LANG_PATH'), $sources);
            $this->appendClassNames($registry['commands'], $provider_class::declaration('COMMANDS'));
            $this->appendPanelProviders(
                $registry['panels'],
                $registry['panel_owners'],
                $provider_class::declaration('PANELS'),
                $package_name,
                $context['type'],
                $composer->getPackage()->getName(),
            );
            $registry['assets'][$prefix] ??= [];
            $this->appendFiles($registry['assets'][$prefix], $provider_class::declaration('ASSETS'), $sources);
            $registry['asset_roots'][$package_name] ??= [
                'package_root' => dirname($context['manifest']),
                'roots' => [],
            ];
            $this->appendAssetRoots(
                $registry['asset_roots'][$package_name]['roots'],
                $provider_class::declaration('PUBLIC_ASSETS'),
                $sources,
                $registry['asset_roots'][$package_name]['package_root'],
            );
            $vite_manifest = $registry['asset_roots'][$package_name]['package_root'].'/dist/.vite/manifest.json';
            if (is_file($vite_manifest)) {
                $vite_contents = file_get_contents($vite_manifest);
                $vite_data = is_string($vite_contents) ? json_decode($vite_contents, true) : null;
                if (! is_array($vite_data)) {
                    throw new RuntimeException('Invalid Vite manifest for package: '.$package_name);
                }
                $registry['vite'][$package_name] = $vite_data;
                $sources[] = $this->absoluteSource($vite_manifest);
            }
        }

        $this->validateBindingAliases($registry['bindings']);
        $registry['providers'] = array_values(array_unique($registry['providers']));
        ksort($registry['packages'], SORT_STRING);
        $registry['routes'] = array_values(array_unique($registry['routes']));
        foreach (['commands', 'panels'] as $key) {
            $registry[$key] = array_values(array_unique($registry[$key]));
        }
        $registry['build_artifacts'] = array_values(array_unique($registry['build_artifacts']));
        $this->appendComponentCatalog($registry['component_catalog'], $registry['components']);
        $registry['component_catalog_pages'] = $this->componentCatalogPages($registry['component_catalog']);
        $registry['component_catalog_summary'] = [
            'version' => hash('sha256', serialize($registry['component_catalog_pages'])),
            'count' => array_sum(array_map('count', $registry['component_catalog'])),
            'page_count' => max(1, count($registry['component_catalog_pages'])),
            'page_size' => GetWebkernelComponentCatalog::PAGE_SIZE,
        ];
        foreach (['views', 'components', 'assets'] as $key) {
            foreach ($registry[$key] as &$directories) {
                $directories = array_values(array_unique($directories));
            }
            unset($directories);
        }
        foreach ($registry['asset_roots'] as &$asset_declaration) {
            $asset_declaration['roots'] = array_values(array_unique($asset_declaration['roots']));
        }
        unset($asset_declaration);

        $registry['sources'] = array_values(array_unique($sources));

        return $registry;
    }

    /** @param $target list<string> Package-owned public asset roots. @param $directories array<mixed> Declared directories. @param $sources list<string> Watched declaration sources. @param $package_root string Canonical package root. */
    private function appendAssetRoots(array &$target, array $directories, array &$sources, string $package_root): void
    {
        foreach ($directories as $directory) {
            if (! is_string($directory)) {
                throw new RuntimeException('Public asset declarations must contain directory paths.');
            }
            $path = realpath($directory);
            if ($path === false || ! is_dir($path) || ! str_starts_with($path, rtrim($package_root, '/').'/')) {
                throw new RuntimeException('Declared public asset directory must exist inside its package: ' . $directory);
            }
            $target[] = $path;
            $sources[] = $path;
        }
    }

    /** @param array<string, list<string>> $target @param array<string, list<string>> $directories */
    private function appendComponentCatalog(array &$target, array $directories): void
    {
        foreach ($directories as $namespace => $roots) {
            foreach ($roots as $root) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
                );
                foreach ($iterator as $file) {
                    if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.view.php')) {
                        continue;
                    }
                    $relative = substr($file->getPathname(), strlen($root) + 1, -strlen('.view.php'));
                    $name = str_replace(DIRECTORY_SEPARATOR, '.', $relative);
                    if (in_array($name, ['assets', 'styles'], true)) {
                        continue;
                    }
                    $target[$namespace][] = $name;
                }
            }
        }
        foreach ($target as &$names) {
            $names = array_values(array_unique($names));
            sort($names, SORT_STRING);
        }
        unset($names);
        ksort($target, SORT_STRING);
    }

    /** @param array<string, list<string>> $catalog @return list<array<string, list<string>>> */
    private function componentCatalogPages(array $catalog): array
    {
        $pages = [];
        $position = 0;
        foreach ($catalog as $namespace => $names) {
            foreach ($names as $name) {
                $page = intdiv($position, GetWebkernelComponentCatalog::PAGE_SIZE);
                $pages[$page][$namespace][] = $name;
                $position++;
            }
        }

        return array_values($pages);
    }

    /** @param list<array<string, list<string>>> $pages @param array{version: string, count: int, page_count: int, page_size: int} $summary */
    private function publishComponentCatalogPages(string $manifest_directory, array $pages, array $summary): void
    {
        $version = $summary['version'];
        foreach ($pages as $index => $page) {
            $page_number = $index + 1;
            $this->writeManifest(
                $manifest_directory.'/webkernel_component_catalog_'.$version.'_page_'.$page_number.'.php',
                $page,
            );
        }

        $this->writeManifest($manifest_directory.'/webkernel_component_catalog_summary.php', $summary);

        foreach (glob($manifest_directory.'/webkernel_component_catalog_*_page_*.php') ?: [] as $path) {
            if (! str_contains(basename($path), $version)) {
                @unlink($path);
                if (function_exists('opcache_invalidate')) {
                    opcache_invalidate($path, true);
                }
            }
        }
    }

    /** @param $target array<string, list<string>> Destination map. @param $files array<mixed> Declared paths. @param $sources list<string> Watched source paths. */
    private function appendFiles(array &$target, array $files, array &$sources): void
    {
        foreach ($files as $file) {
            if (! is_string($file)) {
                throw new RuntimeException('Service provider file declarations must contain strings.');
            }
            $path = $this->absoluteSource($file);
            if (! is_file($path)) {
                throw new RuntimeException('Declared package file does not exist: ' . $path);
            }
            $target[] = $path;
            $sources[] = $path;
        }
    }

    /** @param $target array<string, list<string>> Destination map. @param $prefix string Resource namespace. @param $directories array<mixed> Declared directories. @param $sources list<string> Watched source paths. */
    private function appendDirectories(array &$target, string $prefix, array $directories, array &$sources): void
    {
        foreach ($directories as $directory) {
            if (! is_string($directory)) {
                throw new RuntimeException('Service provider directory declarations must contain strings.');
            }
            $path = realpath($directory);
            if ($path === false || ! is_dir($path)) {
                throw new RuntimeException('Declared package directory does not exist: ' . $directory);
            }
            $target[$prefix][] = $path;
            $sources[] = $path;
        }
    }


    /** @param $target list<string> Destination list. @param $classes array<mixed> Declared class names. */
    private function appendClassNames(array &$target, array $classes): void
    {
        foreach ($classes as $class) {
            if (! is_string($class) || ! class_exists($class)) {
                throw new RuntimeException('Declared command classes must exist: '.(is_string($class) ? $class : get_debug_type($class)));
            }
            $target[] = $class;
        }
    }

    /** @param list<string> $classes @param array<string, array{owner: string, type: string, root: string}> $owners */
    private function appendPanelProviders(array &$target, array &$owners, array $classes, string $owner, string $owner_type, string $root_package): void
    {
        foreach ($classes as $class) {
            if (! is_string($class) || ! class_exists($class) || ! is_subclass_of($class, PanelProvider::class)) {
                throw new RuntimeException('Panel providers must extend '.PanelProvider::class.': '.(is_string($class) ? $class : get_debug_type($class)));
            }
            $target[] = $class;
            $owners[$class] = ['owner' => $owner, 'type' => $owner_type, 'root' => $root_package];
        }
    }

    /** @param $target array<string, string|array{concrete: string, lifetime?: string}|array{alias: string}> Compiled bindings. @param $bindings array<mixed> Provider bindings. */
    private function appendBindings(array &$target, array $bindings): void
    {
        foreach ($bindings as $abstract => $binding) {
            if (! is_string($abstract) || $abstract === '' || isset($target[$abstract])) {
                throw new RuntimeException('Service provider bindings require unique, non-empty abstract names.');
            }
            if (is_array($binding) && array_key_exists('alias', $binding)) {
                if (count($binding) !== 1 || ! is_string($binding['alias']) || trim($binding['alias']) !== $binding['alias'] || $binding['alias'] === '') {
                    throw new RuntimeException(sprintf('Invalid container alias declaration for "%s".', $abstract));
                }
                $target[$abstract] = ['alias' => $binding['alias']];
                continue;
            }
            $concrete = is_string($binding)
                ? $binding
                : (is_array($binding) ? ($binding['concrete'] ?? null) : null);
            $lifetime = is_string($binding)
                ? 'transient'
                : (is_array($binding) ? ($binding['lifetime'] ?? 'transient') : 'transient');
            if (
                ! is_string($concrete)
                || $concrete === ''
                || ! in_array($lifetime, ['transient', 'singleton', 'scoped'], true)
            ) {
                throw new RuntimeException(sprintf('Invalid container binding declaration for "%s".', $abstract));
            }
            $target[$abstract] = ['concrete' => $concrete, 'lifetime' => $lifetime];
        }
    }

    /** @param array<string, string|array{concrete: string, lifetime?: string}|array{alias: string}> $bindings */
    private function validateBindingAliases(array $bindings): void
    {
        foreach ($bindings as $abstract => $declaration) {
            if (! is_array($declaration) || ! isset($declaration['alias'])) {
                continue;
            }

            $seen = [$abstract => true];
            $target = $declaration['alias'];
            while (isset($bindings[$target]) && is_array($bindings[$target]) && isset($bindings[$target]['alias'])) {
                if (isset($seen[$target])) {
                    throw new RuntimeException(sprintf('Circular container alias detected for "%s".', $abstract));
                }
                $seen[$target] = true;
                $target = $bindings[$target]['alias'];
            }
        }
    }

    /** @param $target array<string, string|array{0: string, 1: string}> Compiled view directive handlers. @param $directives array<mixed> Provider directives. */
    private function appendViewDirectives(array &$target, array $directives, string $provider): void
    {
        foreach ($directives as $name => $handler) {
            if (
                ! is_string($name)
                || preg_match('/\\A[a-zA-Z_][a-zA-Z0-9_]*\\z/', $name) !== 1
                || array_key_exists($name, $target)
                || (! is_string($handler) && !(is_array($handler) && array_is_list($handler) && count($handler) === 2 && is_string($handler[0]) && is_string($handler[1])))
                || ! is_callable($handler)
            ) {
                throw new RuntimeException(sprintf('Invalid or duplicate view directive declaration "%s" in %s.', (string) $name, $provider));
            }
            $target[$name] = $handler;
        }
    }

    /** @param list<string|array{0: string, 1: string}> $target @param array<mixed> $callables */
    private function appendCallables(array &$target, array $callables, string $kind): void
    {
        foreach ($callables as $callable) {
            if (
                (! is_string($callable) && !(is_array($callable) && array_is_list($callable) && count($callable) === 2 && is_string($callable[0]) && is_string($callable[1])))
                || ! is_callable($callable)
            ) {
                throw new RuntimeException('Declared '.$kind.'s must be callable function names or static class methods.');
            }
            $target[] = $callable;
        }
    }

    private function prefix(mixed $prefix, string $package_name, bool $root): string
    {
        if ($prefix === null || $prefix === '') {
            $prefix = $root ? 'app' : substr($package_name, strpos($package_name, '/') + 1);
            if (! $root) {
                $prefix = trim((string) preg_replace('/[^a-zA-Z0-9_-]+/', '-', $prefix), '-');
            }
        }
        if (! is_string($prefix) || preg_match('/\A[a-zA-Z0-9_-]+\z/', $prefix) !== 1) {
            throw new RuntimeException(sprintf('Invalid resource prefix for package "%s".', $package_name));
        }

        return $prefix;
    }

    private function packageManifestPath(Composer $composer, PackageInterface $package, string $root_path, string $root_manifest): string
    {
        if ($package === $composer->getPackage()) {
            return $this->absoluteSource($root_manifest);
        }

        $install_path = $composer->getInstallationManager()->getInstallPath($package);
        if (! is_string($install_path)) {
            throw new RuntimeException(sprintf('Unable to resolve package path: %s', $package->getName()));
        }

        return $this->absoluteSource($install_path . '/composer.json');
    }

    private function absoluteSource(string $path): string
    {
        $resolved = realpath($path);
        if ($resolved === false) {
            throw new RuntimeException('Unable to resolve declared source path: ' . $path);
        }

        return $resolved;
    }

    private static function isAbsolute(string $path): bool
    {
        return str_starts_with($path, '/') || preg_match('/\A[A-Za-z]:[\\\\\/]/', $path) === 1;
    }

    private function writeManifest(string $path, array $declarations): void
    {
        $contents = "<?php declare(strict_types=1);\nreturn "
            . var_export($declarations, true)
            . ";\n";
        $temporary = $path . '.' . bin2hex(random_bytes(8)) . '.tmp';
        if (@file_put_contents($temporary, $contents, LOCK_EX) !== strlen($contents)) {
            @unlink($temporary);
            throw new RuntimeException('Unable to write compiled Webkernel manifest: ' . $path);
        }
        if (! @rename($temporary, $path)) {
            @unlink($temporary);
            throw new RuntimeException('Unable to publish compiled Webkernel manifest: ' . $path);
        }
        clearstatcache(true, $path);
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($path, true);
        }
    }

    /** Publishes the generated artefact list used by the application fast boot. */
    private function publishBootManifest(string $root_path, string $manifest_directory, array $keys, array $catalog_pages, array $catalog_summary, array $paths): void
    {
        $manifest_path = $paths['boot_manifest'] ?? null;
        if ($manifest_path === null) {
            return;
        }
        if (! is_string($manifest_path) || $manifest_path === '' || self::isAbsolute($manifest_path) || str_contains($manifest_path, '..')) {
            throw new RuntimeException('config/paths.php must define a valid boot_manifest path when fast boot validation is enabled.');
        }

        $artifacts = array_map(
            static fn (string $key): string => $manifest_directory . '/webkernel_' . $key . '.php',
            $keys,
        );
        $artifacts[] = $manifest_directory . '/webkernel_component_catalog_summary.php';
        foreach (array_keys($catalog_pages) as $page_number) {
            $artifacts[] = $manifest_directory . '/webkernel_component_catalog_' . $catalog_summary['version'] . '_page_' . ($page_number + 1) . '.php';
        }

        $absolute_manifest = $root_path . '/' . ltrim($manifest_path, '/');
        $directory = dirname($absolute_manifest);
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException('Unable to create the fast boot manifest directory: ' . $directory);
        }

        $relative_artifacts = array_map(
            static fn (string $path): string => ltrim(substr($path, strlen($root_path)), '/'),
            $artifacts,
        );
        $contents = "<?php declare(strict_types=1);\nreturn " . var_export(['artifacts' => $relative_artifacts], true) . ";\n";
        $temporary = $absolute_manifest . '.' . bin2hex(random_bytes(8)) . '.tmp';
        if (@file_put_contents($temporary, $contents, LOCK_EX) !== strlen($contents) || ! @rename($temporary, $absolute_manifest)) {
            @unlink($temporary);
            throw new RuntimeException('Unable to publish the fast boot manifest: ' . $absolute_manifest);
        }
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($absolute_manifest, true);
        }
    }
}
