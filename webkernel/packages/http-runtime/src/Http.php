<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel;

use Webkernel\Codebase\Assets\PackageAssetRegistry;
use RuntimeException;
use Webkernel\Config\Config;
use Webkernel\Container\Container;
use Webkernel\Codebase\Http\ResponseHeaders;
use Webkernel\Csrf;
use Webkernel\Getters\GetAsset;
use Webkernel\I18n\I18n;
use Webkernel\I18n\I18nContext;
use Webkernel\Route\Route;
use Webkernel\Route\Request;
use Webkernel\View\View;
use Webkernel\HttpErrorPages\ErrorPages;
use Webkernel\HttpFoundation\Exceptions\HttpResponseException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** Initializes the compiled HTTP resources and dispatches one request. */
final class Http
{
    /** @var array<string, mixed>|null */
    private static ?array $site_configuration_cache = null;

    private static ?string $site_configuration_root = null;

    private function __construct() {}

    public static function run(): void
    {
        $profile_request = (\getenv('WEBKERNEL_PROFILE_REQUEST') ?: '') === '1';
        $phase_started_at = \hrtime(true);
        $phases = [];
        $mark_phase = static function (string $name) use (&$phase_started_at, &$phases, $profile_request): void {
            if (! $profile_request) {
                return;
            }

            $now = \hrtime(true);
            $phases[$name] = ($now - $phase_started_at) / 1e6;
            $phase_started_at = $now;
        };
        $publish_timings = static function () use (&$phases, $profile_request, $mark_phase): void {
            if (! $profile_request || \headers_sent()) {
                return;
            }

            $mark_phase('response_prepare');
            $entries = ['webkernel_total;dur='.\number_format(\request_time(), 3, '.', '')];
            foreach ($phases as $name => $duration) {
                $entries[] = $name.';dur='.\number_format($duration, 3, '.', '');
            }
            $request_phases = $GLOBALS['webkernel_request_phase_timings'] ?? [];
            if (\is_array($request_phases)) {
                foreach ($request_phases as $name => $duration) {
                    if (\is_string($name) && \is_numeric($duration)) {
                        $entries[] = $name.';dur='.\number_format((float) $duration, 3, '.', '');
                    }
                }
            }
            if ((\getenv('WEBKERNEL_PROFILE_VIEWS') ?: '') === '1') {
                $view_metrics = $GLOBALS['webkernel_view_exclusive_metrics'] ?? [];
                if (\is_array($view_metrics)) {
                    \arsort($view_metrics, SORT_NUMERIC);
                    foreach (\array_slice($view_metrics, 0, 12, true) as $view => $duration) {
                        if (\is_string($view) && \is_numeric($duration)) {
                            $view_name = \preg_replace('/[^a-zA-Z0-9_-]+/', '_', $view) ?? 'unknown';
                            $view_name = \trim(\substr($view_name, 0, 48), '_');
                            $entries[] = 'view_'.$view_name.'_'.\substr(\hash('sha256', $view), 0, 6).';dur='.
                                \number_format((float) $duration, 3, '.', '');
                        }
                    }
                }
            }
            \header('Server-Timing: '.\implode(', ', $entries));
        };

        if ($profile_request) {
            $GLOBALS['webkernel_request_phase_timings'] = [];
        }
        if ((\getenv('WEBKERNEL_PROFILE_VIEWS') ?: '') === '1') {
            $GLOBALS['webkernel_view_exclusive_metrics'] = [];
        }

        PackageAssetRegistry::beginRequest();
        GetAsset::beginRequest();
        $root_path = base_path();
        $vendor_path = vendor_path();
        $site_configuration = self::siteConfiguration($root_path);
        $mark_phase('preflight');

        Config::boot($root_path, $vendor_path, $root_path . '/runtime/cache');
        $mark_phase('config');

        $header_configuration = Config::get('http-headers', []);
        if (! is_array($header_configuration)) {
            throw new RuntimeException('HTTP response header configuration must be an array.');
        }
        $secure_request = isset($_SERVER['HTTPS'])
            && is_string($_SERVER['HTTPS'])
            && $_SERVER['HTTPS'] !== ''
            && strtolower($_SERVER['HTTPS']) !== 'off';
        $csp_nonce = ResponseHeaders::apply($header_configuration, $secure_request);

        Container::boot($site_configuration['bindings'] ?? [])->beginScope();
        foreach (($site_configuration['boot'] ?? []) as $callback) {
            if (! is_callable($callback)) {
                throw new RuntimeException('Site boot declarations must be callable.');
            }
            $callback();
        }
        $views = $site_configuration['views'] ?? [];
        $components = $site_configuration['components'] ?? [];
        $view_directives = $site_configuration['view_directives'] ?? [];
        $lang_paths = $site_configuration['lang'] ?? [];
        PackageAssetRegistry::boot(self::assetRoots($root_path, $site_configuration));
        $GLOBALS['webkernel_packages'] = self::packageMetadata($site_configuration);
        GetAsset::boot($site_configuration['vite'] ?? []);
        Route::useDeclaredRouteFiles($site_configuration['routes'] ?? [], $site_configuration['route_dependencies'] ?? []);
        View::boot($views, $components, $view_directives);
        View::share('csp_nonce', $csp_nonce);

        I18n::boot($lang_paths);
        I18nContext::flush();
        $mark_phase('services');

        if (! Csrf::protectRequest()) {
            http_response_code(419);
            $publish_timings();
            echo ErrorPages::render(419);

            return;
        }

        Container::getInstance()->bind(
            Request::class,
            static fn (): Request => Route::currentRequest(),
            'scoped',
        );
        $mark_phase('security');

        ob_start();
        try {
            Route::run();
            $body = (string) ob_get_clean();
            $mark_phase('route_render');
            $publish_timings();
            $status = http_response_code();
            if (is_int($status) && $status >= 400) {
                echo ErrorPages::render($status);
                return;
            }
            echo $body;
        } catch (HttpResponseException $exception) {
            while (ob_get_level() > 0) { ob_end_clean(); }
            $publish_timings();
            $exception->getResponse()->send();
        } catch (HttpExceptionInterface $exception) {
            while (ob_get_level() > 0) { ob_end_clean(); }
            $status = $exception->getStatusCode();
            http_response_code($status);
            foreach ($exception->getHeaders() as $name => $values) {
                foreach ((array) $values as $value) {
                    if (is_string($name) && is_string($value)) {
                        header($name.': '.$value, false);
                    }
                }
            }
            $publish_timings();
            echo ErrorPages::render($status);
        } catch (\Throwable $exception) {
            while (ob_get_level() > 0) { ob_end_clean(); }
            $publish_timings();
            error_log($exception->__toString());
            http_response_code(500);
            echo ErrorPages::render(500, $exception);
        }
    }

    /** @return array<string, mixed> */
    private static function siteConfiguration(string $root_path): array
    {
        if (self::$site_configuration_cache !== null && self::$site_configuration_root === $root_path) {
            return self::$site_configuration_cache;
        }

        $root_file = $root_path.'/config/site.php';
        $root_configuration = is_file($root_file) ? require $root_file : [];
        if (! is_array($root_configuration)) {
            throw new RuntimeException('config/site.php must return an array.');
        }

        $configuration = [
            'routes' => [], 'views' => [], 'components' => [], 'view_directives' => [],
            'bindings' => [], 'boot' => [], 'lang' => [], 'public_assets' => [], 'configs' => [], 'route_dependencies' => [$root_file, ...glob($root_path.'/config/*.php') ?: []],
        ];
        foreach (\Composer\InstalledVersions::getInstalledPackages() as $package_name) {
            if (! is_string($package_name) || ! \Composer\InstalledVersions::isInstalled($package_name)) {
                continue;
            }
            $package_root = \Composer\InstalledVersions::getInstallPath($package_name);
            if (! is_string($package_root)) {
                continue;
            }
            $package_site_file = rtrim($package_root, '/\\').'/config/site.php';
            if (! is_file($package_site_file)) {
                continue;
            }
            $configuration['route_dependencies'][] = $package_site_file;
            $package_configuration = require $package_site_file;
            if (! is_array($package_configuration)) {
                throw new RuntimeException(sprintf('Composer package %s config/site.php must return an array.', $package_name));
            }
            self::mergeSiteConfiguration($configuration, $package_configuration, $package_name);
            foreach (($package_configuration['configs'] ?? []) as $config_file) {
                if (is_string($config_file) && is_file($config_file)) {
                    Config::addDirectory(dirname($config_file));
                    $configuration['route_dependencies'][] = $config_file;
                }
            }
        }

        unset($root_configuration['packages']);
        self::mergeSiteConfiguration($configuration, $root_configuration, null);
        $configuration['packages'] = array_values(array_unique($configuration['packages'] ?? []));

        foreach (['routes', 'configs', 'route_dependencies'] as $key) {
            $configuration[$key] = self::resolvePaths($root_path, $configuration[$key] ?? []);
        }
        foreach (['views', 'components', 'lang', 'public_assets'] as $key) {
            foreach (($configuration[$key] ?? []) as $namespace => $directories) {
                if (is_array($directories)) {
                    $configuration[$key][$namespace] = self::resolvePaths($root_path, $directories);
                }
            }
        }

        self::$site_configuration_root = $root_path;
        self::$site_configuration_cache = $configuration;

        return $configuration;
    }

    /** @param array<string, mixed> $target @param array<string, mixed> $source */
    private static function mergeSiteConfiguration(array &$target, array $source, ?string $package_name): void
    {
        if ($package_name !== null) {
            $target['packages'][] = $package_name;
        }
        foreach (['routes', 'configs', 'route_dependencies'] as $key) {
            if (is_array($source[$key] ?? null)) {
                $target[$key] = array_values(array_unique([...($target[$key] ?? []), ...$source[$key]]));
            }
        }
        foreach (['views', 'components', 'lang'] as $key) {
            if (! is_array($source[$key] ?? null)) {
                continue;
            }
            $definitions = $source[$key];
            if ($key === 'lang' && array_is_list($definitions)) {
                $definitions = ['package-'.str_replace('/', '-', $package_name ?? 'site') => $definitions];
            }
            foreach ($definitions as $namespace => $directories) {
                if (is_array($directories)) {
                    $target[$key][$namespace] = array_values(array_unique([
                        ...($target[$key][$namespace] ?? []),
                        ...$directories,
                    ]));
                }
            }
        }
        if (is_array($source['public_assets'] ?? null)) {
            $assets = $source['public_assets'];
            if (array_is_list($assets) && $package_name !== null) {
                $assets = [$package_name => $assets];
            }
            foreach ($assets as $name => $directories) {
                if (is_string($name) && is_array($directories)) {
                    $target['public_assets'][$name] = array_values(array_unique([
                        ...($target['public_assets'][$name] ?? []),
                        ...$directories,
                    ]));
                }
            }
        }
        foreach (['bindings', 'view_directives', 'boot'] as $key) {
            if (is_array($source[$key] ?? null)) {
                $target[$key] = array_replace_recursive($target[$key] ?? [], $source[$key]);
            }
        }
    }

    /** @param list<mixed> $paths @return list<string> */
    private static function resolvePaths(string $root_path, array $paths): array
    {
        $resolved = [];
        foreach ($paths as $path) {
            if (! is_string($path) || $path === '') {
                continue;
            }
            $candidate = str_starts_with($path, '/') ? $path : $root_path.'/'.$path;
            $resolved[] = realpath($candidate) ?: $candidate;
        }

        return array_values(array_unique($resolved));
    }

    /** @return array<string, array{package_root: string, roots: list<string>}> */
    private static function assetRoots(string $root_path, array $configuration): array
    {
        $assets = [];
        foreach (($configuration['public_assets'] ?? []) as $package_name => $directories) {
            if (! is_string($package_name) || ! is_array($directories)) {
                continue;
            }
            $package_root = $package_name === (\Composer\InstalledVersions::getRootPackage()['name'] ?? null)
                ? $root_path
                : (\Composer\InstalledVersions::isInstalled($package_name)
                    ? \Composer\InstalledVersions::getInstallPath($package_name)
                    : null);
            if (! is_string($package_root)) {
                continue;
            }
            $package_root = realpath($package_root) ?: $package_root;
            $roots = [];
            foreach ($directories as $directory) {
                if (is_string($directory)) {
                    $candidate = str_starts_with($directory, '/') ? $directory : $root_path.'/'.$directory;
                    if (is_dir($candidate)) {
                        $roots[] = realpath($candidate) ?: $candidate;
                    }
                }
            }
            $assets[$package_name] = ['package_root' => $package_root, 'roots' => $roots];
        }

        return $assets;
    }

    /** @return array<string, array{name: string, version: string, catalog: array}> */
    private static function packageMetadata(array $configuration): array
    {
        $packages = [];
        foreach (($configuration['packages'] ?? []) as $package_name) {
            if (! is_string($package_name) || ! \Composer\InstalledVersions::isInstalled($package_name)) {
                continue;
            }
            $packages[$package_name] = [
                'name' => $package_name,
                'version' => (string) (\Composer\InstalledVersions::getPrettyVersion($package_name) ?? ''),
                'catalog' => [],
            ];
        }
        $root_name = \Composer\InstalledVersions::getRootPackage()['name'] ?? null;
        if (is_string($root_name)) {
            $packages[$root_name] = ['name' => $root_name, 'version' => '', 'catalog' => []];
        }

        return $packages;
    }
}
