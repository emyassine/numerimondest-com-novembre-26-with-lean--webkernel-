<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\View;

use InvalidArgumentException;
use ReflectionFunction;
use ReflectionNamedType;
use Webkernel\View\Compile\Directives;
use Webkernel\View\Compile\Mode;
use Webkernel\Config\Config;
use Webkernel\ServiceProvider\Deployment\DeploymentLock;

/** Resolves compiled view and component manifests and renders named templates. */
final class View
{
    /** @var array<string, \Closure> */
    private static array $macros = [];

    /** @var array<string, list<string>> */
    private static array $directories = [];

    /** @var array<string, list<string>> */
    private static array $component_directories = [];

    /** @var array<string, mixed> */
    private static array $shared = [];

    /** @var array<string, true> */
    private static array $added_locations = [];

    /** @var array<class-string, callable(object): string> */
    private static array $stringable = [];

    private static bool $double_encode = true;

    private static array $composers = [];

    private static array $creators = [];

    private static ?Engine $engine = null;
    private static ?string $cache_directory = null;

    private function __construct() {}

    /** Register a static view API extension without a facade or Laravel dependency. */
    public static function macro(string $name, callable $macro): void
    {
        self::$macros[$name] = $macro instanceof \Closure ? $macro : \Closure::fromCallable($macro);
    }

    public static function mixin(object $mixin, bool $replace = true): void
    {
        $reflection = new \ReflectionClass($mixin);
        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if (! $replace && self::hasMacro($method->name)) { continue; }
            $method->setAccessible(true);
            self::macro($method->name, $method->invoke($mixin));
        }
    }

    public static function hasMacro(string $name): bool
    {
        return isset(self::$macros[$name]);
    }

    public static function flushMacros(): void
    {
        self::$macros = [];
    }

    public static function __callStatic(string $name, array $arguments): mixed
    {
        $macro = self::$macros[$name] ?? null;
        if ($macro === null) {
            throw new \BadMethodCallException('Undefined view macro ['.$name.'].');
        }

        $bound = $macro->bindTo(null, self::class);

        return ($bound ?? $macro)(...$arguments);
    }

    /**
     * Loads Composer-compiled view and component directory maps.
     *
     * @param $manifest array<string, list<string>> View directories by namespace.
     * @param $components array<string, list<string>> Component directories by namespace.
     * @param $directives array<string, callable(string, \Webkernel\View\Compile\State): string> Compiled package directive handlers.
     * @param $cache_directory string|null Optional compiled-view destination.
     */
    public static function boot(array $manifest, array $components = [], array $directives = [], ?string $cache_directory = null): void
    {
        self::$cache_directory = $cache_directory !== null ? rtrim($cache_directory, '/\\') : null;
        self::$directories = $manifest;
        self::$component_directories = $components;
        self::$shared = [];
        self::$added_locations = [];
        self::$engine = null;
        foreach ($directives as $name => $handler) {
            // Old declaration manifests may contain callbacks from a package
            // that was removed. Ignore those entries instead of failing every SSR request.
            if (! is_string($name) || ! is_callable($handler)) {
                continue;
            }
            self::directive($name, $handler);
        }
    }

    /**
     * Creates a renderable view.
     *
     * @param $name string View name, optionally prefixed with `namespace::`.
     * @param $data array<string, mixed> Values made available to the template.
     */
    public static function make(string $name, array $data = []): ViewResult
    {
        [$namespace, $view_name] = self::splitName($name);

        $view = new ViewResult($namespace.'::'.$view_name, $data + self::$shared);
        self::invokeViewCallbacks(self::$creators, $view);

        return $view;
    }

    /** Resolve a view name or an absolute `.view.php` template path. */
    public static function resolve(string $name, array $data = []): ViewResult
    {
        return str_ends_with($name, '.view.php') && is_file($name)
            ? self::file($name, $data)
            : self::make($name, $data);
    }

    /** Creates a namespaced render result for an existing `.view.php` file. */
    public static function file(string $path, array $data = []): ViewResult
    {
        if (! str_ends_with($path, '.view.php') || ! is_file($path)) {
            throw new InvalidArgumentException('View::file() requires an existing .view.php template.');
        }
        $name = 'runtime-file::'.hash('sha256', $path);
        self::engine()->componentView($name, $path);

        return new ViewResult($name, $data + self::$shared);
    }

    /** Render trusted inline template source with Webkernel's view compiler. */
    public static function renderString(string $source, array $data = []): string
    {
        return self::engine()->render_string($source, $data + self::$shared);
    }

    /** Render a layout in the current engine context with one named content section. */
    public static function renderWithSection(string $name, string $section, string $content, array $data = []): string
    {
        $engine = self::engine();
        $engine->set_section($section, $content);
        [$namespace, $view_name] = self::splitName($name);

        return $engine->html($namespace.'::'.$view_name, $data + self::$shared);
    }

    /** @return array<string, list<string>> */
    public static function locations(): array
    {
        $directories = self::$directories;
        $directories['app'] = array_values(array_unique(array_merge($directories['app'] ?? [], array_keys(self::$added_locations))));

        return $directories;
    }

    public static function compile(string $name): void
    {
        self::engine()->compile($name);
    }

    /** Compiles each template declared in package and application view roots. @return list<string> */
    public static function compileAll(): array
    {
        $compiled = [];
        foreach ([self::$directories, self::$component_directories] as $namespaces) {
            foreach ($namespaces as $namespace => $directories) {
                foreach ($directories as $directory) {
                    $iterator = new \RecursiveIteratorIterator(
                        new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
                    );
                    foreach ($iterator as $file) {
                        if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.view.php')) {
                            continue;
                        }
                        $relative = substr($file->getPathname(), strlen(rtrim($directory, '/\\')) + 1, -strlen('.view.php'));
                        $segments = explode(DIRECTORY_SEPARATOR, $relative);
                        $names = [str_replace(DIRECTORY_SEPARATOR, '.', implode(DIRECTORY_SEPARATOR, $segments))];
                        if (end($segments) === 'index' && count($segments) > 1) {
                            array_pop($segments);
                            $names[] = str_replace(DIRECTORY_SEPARATOR, '.', implode(DIRECTORY_SEPARATOR, $segments));
                        }
                        foreach (array_unique($names) as $name) {
                            $name = $name !== '' ? $name : 'index';
                            $view = $namespace.'::'.$name;
                            if (isset($compiled[$view])) {
                                continue;
                            }
                            self::compile($view);
                            $compiled[$view] = true;
                        }
                    }
                }
            }
        }

        return array_keys($compiled);
    }

    /** Sets an explicit compiled-view directory for deployment preparation. */
    public static function setCacheDirectory(string $directory): void
    {
        self::$cache_directory = rtrim($directory, '/\\');
        self::$engine = null;
    }

    /** @param $key string|array<string, mixed> Shared template variable name. @param $value mixed Value available to every view. */
    public static function share(string|array $key, mixed $value = null): void
    {
        if (is_array($key)) {
            self::$shared = array_merge(self::$shared, $key);
        } else {
            self::$shared[$key] = $value;
        }
        self::$engine?->share($key, $value);
    }

    public static function shared(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, self::$shared) ? self::$shared[$key] : $default;
    }

    public static function forget(string $key): void
    {
        unset(self::$shared[$key]);
        self::$engine?->forget($key);
    }

    /** Creates a renderable result for trusted, framework-generated markup. */
    public static function html(string $html, array $data = []): ViewResult
    {
        return new ViewResult('', $data + self::$shared, $html);
    }

    /** Clears static state between requests in a persistent PHP process. */
    public static function flush(): void
    {
        self::$directories = [];
        self::$component_directories = [];
        self::$shared = [];
        self::$added_locations = [];
        self::$stringable = [];
        self::$double_encode = true;
        self::$engine = null;
    }

    public static function exists(string $name): bool
    {
        if (str_ends_with($name, '.view.php') && is_file($name)) {
            return true;
        }

        return self::engine()->template_file($name) !== '';
    }

    public static function componentView(string $name, string $file): void
    {
        self::engine()->componentView($name, $file);
    }

    public static function add_location(string $path): void
    {
        $path = rtrim($path, '/\\');
        if (isset(self::$added_locations[$path])) { return; }
        self::$added_locations[$path] = true;
        self::engine()->add_view_namespace('app', $path);
    }

    /** Register a runtime view or component namespace, for packages with configurable locations. */
    public static function addNamespace(string $namespace, string $path, bool $components = false): void
    {
        $path = rtrim($path, '/\\');
        if ($namespace === '' || $path === '') {
            throw new InvalidArgumentException('A runtime view namespace and directory are required.');
        }
        $locations = $components ? 'component_directories' : 'directories';
        self::${$locations}[$namespace] = array_values(array_unique([
            ...(self::${$locations}[$namespace] ?? []),
            $path,
        ]));
        if ($components) {
            self::engine()->add_component_namespace($namespace, $path);
            self::engine()->add_view_namespace('global', dirname($path));
            return;
        }
        self::engine()->add_view_namespace($namespace, $path);
    }

    public static function directive(string $name, callable $handler): void
    {
        self::compiler()->directive($name, $handler);
        self::$engine?->invalidateCompiledTemplates();
    }

    public static function prepareViewsForCompilationUsing(callable $callback): void
    {
        self::compiler()->prepareViewsForCompilationUsing($callback);
        self::$engine?->invalidateCompiledTemplates();
    }

    public static function if(string $name, callable $callback): void
    {
        self::directives()->condition($name, $callback);
        self::$engine?->invalidateCompiledTemplates();
    }

    /** @param $handler callable(object): string */
    public static function stringable(callable $handler): void
    {
        $reflection = $handler instanceof \Closure
            ? new ReflectionFunction($handler)
            : new ReflectionFunction($handler(...));
        $type = $reflection->getParameters()[0]->getType() ?? null;
        if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            throw new InvalidArgumentException('View::stringable() requires a type-hinted object parameter.');
        }
        self::$stringable[$type->getName()] = $handler;
    }

    public static function without_double_encoding(): void
    {
        self::$double_encode = false;
    }

    public static function compiler(): Compiler
    {
        return self::engine()->compiler();
    }

    public static function directives(): Directives
    {
        return self::engine()->directives();
    }

    public static function check(string $name, mixed ...$arguments): bool
    {
        return self::engine()->check($name, ...$arguments);
    }

    /** Renders a safely escaped template value, preserving explicitly safe HTML values. */
    public static function echo(mixed $value): string
    {
        if ($value instanceof Htmlable) {
            return $value->toHtml();
        }
        if ($value instanceof AttributeBag || $value instanceof Js) {
            return (string) $value;
        }
        if (is_object($value)) {
            foreach (self::$stringable as $class => $handler) {
                if ($value instanceof $class) {
                    return htmlspecialchars($handler($value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', self::$double_encode);
                }
            }
        }

        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', self::$double_encode);
    }

    /** Encode a value safely for an inline JavaScript expression. */
    public static function js(mixed $value): string
    {
        return json_encode($value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
    }

    /** Render app-root Vite entries using Webkernel's Composer-built Vite manifest. */
    public static function vite(string|array $entries): string
    {
        if (! class_exists(\Composer\InstalledVersions::class)) {
            throw new InvalidArgumentException('Composer runtime metadata is required to resolve Vite entries.');
        }
        $package = \Composer\InstalledVersions::getRootPackage()['name'] ?? null;
        if (! is_string($package) || $package === '') {
            throw new InvalidArgumentException('The Composer root package must have a name to resolve Vite entries.');
        }

        $html = '';
        $rendered_styles = [];
        foreach ((array) $entries as $entry) {
            if (! is_string($entry) || $entry === '') {
                throw new InvalidArgumentException('Vite entries must be non-empty strings.');
            }

            if (str_ends_with($entry, '.css')) {
                $styles = \Webkernel\Getters\GetAsset::viteStyles($package, $entry);
                $scripts = null;
            } else {
                $scripts = \Webkernel\Getters\GetAsset::vite($package, $entry);
                $styles = $scripts['css'];
            }

            foreach ($styles as $style) {
                if (isset($rendered_styles[$style])) {
                    continue;
                }
                $rendered_styles[$style] = true;
                $html .= '<link rel="stylesheet" href="'.htmlspecialchars($style, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'">';
            }

            if ($scripts !== null) {
                $html .= '<script type="module" src="'.htmlspecialchars($scripts['js'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'"></script>';
            }
        }

        return $html;
    }

    /** Render Blade-compatible conditional class lists with HTML escaping. */
    public static function class_attribute(array $classes): string
    {
        $tokens = [];
        foreach ($classes as $class => $condition) {
            if (is_int($class)) {
                if (is_string($condition) && $condition !== '') { $tokens[] = $condition; }
            } elseif ($condition) {
                $tokens[] = (string) $class;
            }
        }

        return 'class="'.htmlspecialchars(implode(' ', $tokens), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'"';
    }

    /** Render an inline style map or pass through a literal style string. */
    public static function style_attribute(array|string $styles): string
    {
        if (is_string($styles)) {
            $value = $styles;
        } else {
            $declarations = [];
            foreach ($styles as $property => $style) {
                if (is_int($property)) {
                    if (is_string($style) && trim($style) !== '') { $declarations[] = trim($style); }
                } elseif ($style !== null && $style !== false) {
                    $declarations[] = $property.': '.(is_bool($style) ? ($style ? '1' : '0') : (string) $style);
                }
            }
            $value = implode('; ', $declarations);
        }

        return 'style="'.htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'"';
    }

    public static function conditional_attribute(string $attribute, mixed $condition): string
    {
        return $condition ? $attribute : '';
    }

    public static function engine(): Engine
    {
        if (self::$engine instanceof Engine) {
            return self::$engine;
        }

        $deployed_directory = DeploymentLock::artifactDirectory(base_path(), 'views');
        $mode = $deployed_directory !== null ? Mode::Fast : Mode::Auto;
        // Deployed files come from the map; keep a writable overlay for missing templates.
        $cache_directory = self::$cache_directory ?? base_path('runtime/cache/views');
        $engine = new Engine([], $cache_directory, $mode);
        $engine->set_echo_format('\\' . self::class . '::echo(%s)');
        if ($deployed_directory !== null) {
            $compiled_map_path = $deployed_directory.'/compiled-map.php';
            if (is_file($compiled_map_path)) {
                $compiled_map = require $compiled_map_path;
                if (is_array($compiled_map)) {
                    $engine->set_compiled_map($compiled_map);
                }
            }
        }
        foreach (self::$directories as $namespace => $directories) {
            foreach ($directories as $directory) {
                $engine->add_view_namespace($namespace, $directory);
            }
        }
        foreach (self::$component_directories as $namespace => $directories) {
            foreach ($directories as $directory) {
                $engine->add_component_namespace($namespace, $directory);
                $component_root = dirname($directory);
                $engine->add_view_namespace('global', $component_root);
            }
        }
        if (self::$shared !== []) {
            $engine->share(self::$shared);
        }

        return self::$engine = $engine;
    }

    /** @return array{string, string} */
    private static function splitName(string $name): array
    {
        if (str_contains($name, '::')) {
            [$namespace, $view_name] = explode('::', $name, 2);
        } else {
            $namespace = 'app';
            $view_name = $name;
        }

        if (
            preg_match('/\A[a-zA-Z0-9_-]+\z/', $namespace) !== 1
            || $view_name === ''
            || str_contains($view_name, '..')
            || preg_match('/\A[a-zA-Z0-9_.\/-]+\z/', $view_name) !== 1
        ) {
            throw new InvalidArgumentException('Invalid view name.');
        }

        return [$namespace, str_replace('/', '.', trim($view_name, '/'))];
    }

    public static function render(string $source, array $data = []): string
    {
        return self::renderString($source, $data);
    }

    public static function sharedData(): array { return self::$shared; }

    public static function prependNamespace(string $namespace, array $paths): void
    {
        self::$directories[$namespace] = array_values(array_unique([...$paths, ...(self::$directories[$namespace] ?? [])]));
        self::$engine = null;
    }

    public static function replaceNamespace(string $namespace, array $paths): void
    {
        self::$directories[$namespace] = array_values($paths);
        self::$engine = null;
    }

    public static function composer(array|string $views, callable|string $callback): array
    {
        foreach ((array) $views as $view) { self::$composers[$view][] = $callback; }
        return (array) $views;
    }

    public static function creator(array|string $views, callable|string $callback): array
    {
        foreach ((array) $views as $view) { self::$creators[$view][] = $callback; }
        return (array) $views;
    }

    public static function compose(ViewResult $view): void
    {
        self::invokeViewCallbacks(self::$composers, $view);
    }

    private static function invokeViewCallbacks(array $callbacks, ViewResult $view): void
    {
        foreach ($callbacks as $pattern => $handlers) {
            if (! fnmatch($pattern, $view->name(), FNM_NOESCAPE)) { continue; }
            foreach ($handlers as $handler) {
                if (is_string($handler) && class_exists($handler)) { $handler = [app($handler), 'compose']; }
                $handler($view);
            }
        }
    }
}
