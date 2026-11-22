<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel;

use InvalidArgumentException;
use ReflectionClass;

/** Base for package declarations compiled by Composer and consumed at runtime. */
abstract class ServiceProvider
{
    /**
     * Declares configuration files supplied by this application or package.
     * Use a string key as the configuration section name. The value may be a
     * file path, or an array containing a file path and optional publish metadata.
     * Example:
     *
     * public const array CONFIG = [
     *     'mail' => __DIR__ . '/../../config/mail.php',
     *     'shipping' => [
     *         'path' => __DIR__ . '/../../config/shipping.php',
     *         'publish' => 'shipping.php',
     *         'tag' => 'shipping-config',
     *     ],
     * ];
     *
     * Configuration discovery treats these forms as follows:
     *
     * - A non-empty string value is registered under its string key.
     *   A numeric key uses the file basename as the section name.
     * - An array value is registered only when it has a string path and a string key.
     * - A string publish makes that entry publishable;
     *   A string tag labels it for filtered publishing.
     * - Other value shapes, empty string values, non-string paths,
     *   and arrays with numeric keys are skipped without an exception.
     * - The path is not checked by declaration discovery.
     * If the file is missing when configuration is compiled,
     * it contributes an empty configuration array.
     * If the file exists but returns a non-array, that also contributes an empty array.
     * A PHP error while requiring the file can still interrupt compilation.
     *
     * Use __DIR__ so the path does not depend on the process working directory.
     * Relative or misspelled paths are not corrected automatically.
     *
     * @var array<string|int, string|array{path?: mixed, publish?: mixed, tag?: mixed}>
     */
    public const array CONFIG = [];

    /**
     * Declares explicit dependency-container bindings.
     * Each key is an abstract class or interface name.
     * Each value is a concrete class name, an array with concrete and optional lifetime,
     * or an alias declaration such as `AuthFactory::class => ['alias' => AuthManager::class]`.
     * Example:
     *
     * public const array BINDINGS = [
     *     Clock::class => SystemClock::class,
     *     ReportStore::class => [
     *         'concrete' => SqlReportStore::class,
     *         'lifetime' => 'scoped',
     *     ],
     * ];
     *
     * - A string value defaults to the transient lifetime.
     * - An array value defaults to transient when lifetime is omitted.
     * - Allowed lifetimes are transient, singleton, and scoped.
     * - An alias declaration contains only a non-empty alias target; it resolves through the target's binding and lifetime.
     * - An empty/non-string abstract, empty/non-string concrete,
     * unsupported value shape, invalid lifetime, or duplicate abstract name throws
     * during Composer declaration compilation. No partial binding manifest is published.
     *
     * - Class names are not checked for existence here.
     *   A typo can therefore compile successfully and fail later when
     *   the container tries to resolve that binding.
     *
     * The resulting map is loaded into the container;
     * the provider is not instantiated per request.
     *
     * @var array<class-string, class-string|array{concrete: class-string, lifetime?: 'transient'|'singleton'|'scoped'}|array{alias: class-string}>
     */
    public const array BINDINGS = [];

    /**
     * Declares PHP files that register this package's routes.
     * Example:
     * public const array ROUTES = [__DIR__ . '/../../routes/admin.php'];
     *
     * - Every entry must be a string path. Composer resolves it with realpath() and requires it to be a file.
     * - A non-string entry, path that does not exist, or path resolving to a directory throws during Composer declaration compilation; no route manifest is published for that run.
     * - A valid path is stored in webkernel_routes.php and required by the route runtime.
     * - File contents and PHP syntax are not checked by this declaration compiler. A parse error or runtime exception can fail when the route file is required.
     * - An empty list adds no route files.
     *
     * Prefer __DIR__ paths; a relative path is resolved against the Composer process directory and may therefore point somewhere unintended.
     *
     * @var list<string>
     */
    public const array ROUTES = [];

    /**
     * Declares static callbacks invoked once before any declared route file is loaded.
     * Use this for route macros and package-owned routes that must precede catch-all routes.
     * Each value must be a function name or a static [class, method] callable.
     *
     * @var list<callable(): void>
     */
    public const array ROUTE_BOOTSTRAPPERS = [];

    /** @var list<callable(): void> */
    public const array RUNTIME_BOOTSTRAPPERS = [];

    /**
     * Declares directories containing this package's .view.php templates.
     * Example:
     * public const array VIEWS = [__DIR__ . '/../../resources/views'];
     * A package may also expose explicit namespaces:
     * public const array VIEWS = ['filament-panels' => [__DIR__ . '/../../resources/views']];
     *
     * - Every entry must be a string resolving to an existing directory.
     * - A non-string entry, missing path, or regular file throws during Composer declaration compilation; it is not skipped.
     * - An empty list registers no package views.
     * - A list groups directories under this package's resource prefix; a namespace map records each directory under its explicit view namespace.
     * - This declaration does not inspect template contents. A malformed template is encountered later when that view is compiled or rendered.
     *
     * @var list<string>|array<string, list<string>>
     */
    public const array VIEWS = [];

    /**
     * Declares custom view compiler directives as directive-name => callable.
     * A callable may be a function name or a static [class, method] pair; it
     * receives the directive expression, including its parentheses, and must
     * return PHP source for Webkernel's compiled view.
     *
     * @var array<string, callable(string, \Webkernel\View\Compile\State): string>
     */
    public const array VIEW_DIRECTIVES = [];

    /**
     * Declares directories containing this package's view components.
     * Use a list for the package's normal resource namespace, or a map to assign explicit namespaces.
     * Examples:
     *
     * public const array COMPONENTS = [__DIR__ . '/../../resources/views/components'];
     *
     * public const array COMPONENTS = [
     *     'admin' => [__DIR__ . '/../../resources/views/admin/components'],
     * ];
     *
     * - If the array is a PHP list, every value is treated as a directory under the package's normal resource prefix. The empty list registers no components.
     * - Otherwise, each key must be a string namespace containing only letters, digits, _, or -; its value must be an array of directory paths.
     * - A bad namespace, non-array namespace value, non-string path, missing path, or path resolving to a file throws during Composer declaration compilation.
     * - Valid directories are recorded in the compiled component manifest. Template naming and component lookup errors occur later when rendering requests those components.
     *
     * @var list<string>|array<string, list<string>>
     */
    public const array COMPONENTS = [];

    /**
     * Declares roots containing this package's translation catalogs.
     * Each root uses the locale layout {root}/{locale}/translations.php.
     * Example:
     * public const array LANG_PATH = [__DIR__ . '/../../resources/lang'];
     *
     * - Every entry must be a string resolving to an existing directory.
     * - A non-string entry, missing path, or regular file throws during Composer declaration compilation.
     * - An empty list contributes no package translation roots.
     * - The compiler validates the root directory only; it does not require every locale or translations.php file to exist.
     * - If a locale catalog is absent, that package contributes no entries from that catalog. A present catalog that cannot be loaded or has an invalid return value is handled by the I18n catalog loader.
     *
     * @var list<string>|array<string, list<string>>
     */
    public const array LANG_PATH = [];

    /**
     * Declares classes whose attributed methods may be exposed as console commands.
     * Example:
     * public const array COMMANDS = [ClearCacheCommand::class, BuildAssetsCommand::class];
     *
     * - Each value must be a string naming a class that Composer can autoload when declarations are compiled.
     * - A non-string or class that cannot be loaded throws and stops declaration compilation.
     * - The compiler does not require the class to contain a #[ConsoleCommand] method. A class without one is included in the manifest but produces no command definitions.
     * - Invalid command attributes or duplicate command names are discovered later by console command discovery.
     * - An empty list adds no package command classes.
     *
     * @var list<class-string>
     */
    public const array COMMANDS = [];

    /**
     * Declares panel provider classes owned by this application or package.
     * Example:
     * public const array PANELS = [AdminPanelProvider::class];
     *
     * - Each value must be a string naming an autoloadable class. A non-string or missing class throws during Composer declaration compilation.
     * - The compiler does not check a panel interface, base class, registration method, panel type, or permission policy; it only checks that the class exists.
     * - Valid PanelProvider class names are written to webkernel_panels.php. Their panel definitions are not evaluated during Composer dump; the route compiler consumes them when routes need compiling.
     * - An empty list declares no panels.
     *
     * @var list<class-string>
     */
    public const array PANELS = [];

    /** Website providers share the panel registry and route compiler. */
    public const array WEBSITES = [];

    /**
     * Declares frontend source files that a package build uses as entry points.
     * These are filesystem paths, not browser URLs and not permission to serve the source files.
     * Example:
     *
     * public const array ASSETS = [
     *     __DIR__ . '/../../resources/js/admin.js',
     *     __DIR__ . '/../../resources/css/admin.css',
     * ];
     *
     * - Every value must be a string resolving to an existing file. A non-string, missing path, or directory throws during Composer declaration compilation.
     * - An empty list records no frontend entry points.
     * - Valid paths are recorded under the package resource prefix in webkernel_assets.php.
     * - This manifest does not currently run Vite, compile JavaScript or CSS, resolve a Vite manifest, or make an entry point publicly accessible.
     * - Use PUBLIC_ASSETS to declare directories of files Webkernel is allowed to serve. The asset route supports only its documented MIME types; other extensions return HTTP 404.
     *
     * @var list<string>
     */
    public const array ASSETS = [];

    /**
     * Declares package directories from which approved static files may be served over HTTP.
     * Example:
     *
     * public const array PUBLIC_ASSETS = [
     *     __DIR__ . '/../../resources/assets',
     *     __DIR__ . '/../../dist',
     * ];
     *
     * Build a URL with GetAsset::url('vendor/package', 'resources/assets/logo.svg'). The file path passed to GetAsset::url() is relative to the package root, and must fall beneath one of these declared directories.
     *
     * - Every value must be a string resolving to an existing directory physically inside the package root.
     * - A non-string, missing path, regular file, or directory resolving outside the package root throws during Composer declaration compilation.
     * - An empty list makes no files in this package available through the generic package asset route.
     * - At URL generation, an unknown package, unsafe relative path, or path outside declared roots throws InvalidArgumentException.
     * - At request time, the target must exist as a file and resolve canonically inside a declared root. Traversal segments and symlink escapes are rejected.
     * - Only supported extensions (CSS, JavaScript, common image formats, and WOFF/WOFF2 fonts) are served. An undeclared root, missing file, unsupported extension, or failed file metadata/read returns HTTP 404.
     * - Files are served from their installed package location. They are not copied or symlinked into the application's public/ directory.
     *
     * @var list<string>
     */
    public const array PUBLIC_ASSETS = [];

    /**
     * Returns one declaration without instantiating the provider.
     *
     * @param $name string Declaration constant name.
     * @return array<mixed>
     * @throws InvalidArgumentException When the declaration is unknown or not an array.
     */
    final public static function declaration(string $name): array
    {
        if (! in_array($name, [
            'CONFIG',
            'BINDINGS',
            'ROUTES',
            'ROUTE_BOOTSTRAPPERS',
            'RUNTIME_BOOTSTRAPPERS',
            'VIEWS',
            'VIEW_DIRECTIVES',
            'COMPONENTS',
            'LANG_PATH',
            'COMMANDS',
            'PANELS',
            'WEBSITES',
            'ASSETS',
            'PUBLIC_ASSETS',
        ], true)) {
            throw new InvalidArgumentException(sprintf('Unknown Webkernel declaration: %s', $name));
        }

        $constant = (new ReflectionClass(static::class))->getReflectionConstant($name);
        $value = $constant?->getValue() ?? [];
        if ($name === 'PANELS' || $name === 'WEBSITES') {
            $panels = (new ReflectionClass(static::class))->getReflectionConstant('PANELS')?->getValue() ?? [];
            $websites = (new ReflectionClass(static::class))->getReflectionConstant('WEBSITES')?->getValue() ?? [];
            if (! is_array($panels) || ! is_array($websites)) {
                throw new InvalidArgumentException(sprintf('%s::PANELS and WEBSITES must be arrays.', static::class));
            }
            $value = array_values(array_unique([...$panels, ...$websites]));
        }
        if (! is_array($value)) {
            throw new InvalidArgumentException(sprintf('%s::%s must be an array.', static::class, $name));
        }

        return $value;
    }
}
