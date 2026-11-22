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
use Webkernel\Liveview\Mechanisms\HandleComponents\CorruptComponentPayloadException;

/** Initializes the compiled HTTP resources and dispatches one request. */
final class Http
{
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
            if ((\getenv('WEBKERNEL_PROFILE_COMPONENTS') ?: '') === '1') {
                $component_metrics = $GLOBALS['webkernel_liveview_component_exclusive_metrics'] ?? [];
                if (\is_array($component_metrics)) {
                    \arsort($component_metrics, SORT_NUMERIC);
                    foreach (\array_slice($component_metrics, 0, 12, true) as $component => $duration) {
                        if (\is_string($component) && \is_numeric($duration)) {
                            $component_name = \preg_replace('/[^a-zA-Z0-9_-]+/', '_', $component) ?? 'unknown';
                            $component_name = \trim(\substr($component_name, 0, 36), '_');
                            $entries[] = 'component_'.$component_name.'_'.\substr(\hash('sha256', $component), 0, 6).';dur='.
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
        if ((\getenv('WEBKERNEL_PROFILE_COMPONENTS') ?: '') === '1') {
            $GLOBALS['webkernel_liveview_component_exclusive_metrics'] = [];
        }

        PackageAssetRegistry::beginRequest();
        GetAsset::beginRequest();
        $root_path = base_path();
        $vendor_path = vendor_path();
        $manifest_path = $root_path . '/runtime/cache/declarations/';
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

        Container::boot(self::manifest($manifest_path . 'webkernel_bindings.php'))->beginScope();
        $views = self::manifest($manifest_path . 'webkernel_views.php');
        $components = self::manifest($manifest_path . 'webkernel_components.php');
        $view_directives = self::manifest($manifest_path . 'webkernel_view_directives.php');
        $lang_paths = self::manifest($manifest_path . 'webkernel_lang.php');
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
        } catch (CorruptComponentPayloadException $exception) {
            while (ob_get_level() > 0) { ob_end_clean(); }
            http_response_code(419);
            $publish_timings();
            echo ErrorPages::render(419);
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

    /** @return array<mixed> */
    private static function manifest(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }

        $manifest = require $path;
        return is_array($manifest) ? $manifest : [];
    }
}
