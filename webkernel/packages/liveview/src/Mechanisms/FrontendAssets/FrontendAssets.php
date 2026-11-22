<?php

namespace Webkernel\Liveview\Mechanisms\FrontendAssets;

use Webkernel\Liveview\Drawer\Utils;
use Webkernel\Route\Route;
use Webkernel\View\View;
use Webkernel\Liveview\Webkernel\Runtime;
use Webkernel\Liveview\Mechanisms\Mechanism;
use Webkernel\Liveview\Mechanisms\HandleRequests\EndpointResolver;
use function Webkernel\Liveview\on;

class FrontendAssets extends Mechanism
{
    public $hasRenderedScripts = false;
    public $hasRenderedStyles = false;

    public $javaScriptRoute;

    public $scriptTagAttributes = [];

    public function boot()
    {
        app($this::class)->setScriptRoute(function ($handle) {
            return config('app.debug')
                ? Route::get(EndpointResolver::scriptPath(minified: false), $handle)
                : Route::get(EndpointResolver::scriptPath(minified: true), $handle);
        });

        Route::get(EndpointResolver::mapPath(csp: false), [static::class, 'maps']);
        Route::get(EndpointResolver::mapPath(csp: true), [static::class, 'cspMaps']);

        View::directive('livewireScripts', [static::class, 'livewireScripts']);
        View::directive('livewireScriptConfig', [static::class, 'livewireScriptConfig']);
        View::directive('livewireStyles', [static::class, 'livewireStyles']);

        on('flush-state', function () {
            $instance = app(static::class);

            $instance->hasRenderedScripts = false;
            $instance->hasRenderedStyles = false;
        });
    }

    function useScriptTagAttributes($attributes)
    {
        $this->scriptTagAttributes = array_merge($this->scriptTagAttributes, $attributes);
    }

    function setScriptRoute($callback)
    {
        $route = $callback([self::class, 'returnJavaScriptAsFile'], EndpointResolver::scriptPath());

        $this->javaScriptRoute = $route;
    }

    public static function livewireScripts($expression): string
    {
        return '<?php echo \Webkernel\Liveview\Mechanisms\FrontendAssets\FrontendAssets::scripts'.$expression.'; ?>';
    }

    public static function livewireScriptConfig($expression): string
    {
        return '<?php echo \Webkernel\Liveview\Mechanisms\FrontendAssets\FrontendAssets::scriptConfig'.$expression.'; ?>';
    }

    public static function livewireStyles($expression): string
    {
        return '<?php echo \Webkernel\Liveview\Mechanisms\FrontendAssets\FrontendAssets::styles'.$expression.'; ?>';
    }

    public function returnJavaScriptAsFile()
    {
        Runtime::boot();

        $isCsp = app('liveview')->isCspSafe();

        if (config('app.debug')) {
            $file = $isCsp ? 'liveview.csp.js' : 'liveview.js';
        } else {
            $file = $isCsp ? 'liveview.csp.min.js' : 'liveview.min.js';
        }

        return Utils::pretendResponseIsFile(__DIR__.'/../../../dist/'.$file);
    }

    public function maps()
    {
        Runtime::boot();

        $file = app('liveview')->isCspSafe()
            ? 'liveview.csp.min.js.map'
            : 'liveview.min.js.map';

        return Utils::pretendResponseIsFile(__DIR__.'/../../../dist/'.$file);
    }

    public function cspMaps()
    {
        Runtime::boot();

        return Utils::pretendResponseIsFile(__DIR__.'/../../../dist/liveview.csp.min.js.map');
    }

    public static function styles($options = []): string
    {
        if (app(static::class)->hasRenderedStyles) return '';

        app(static::class)->hasRenderedStyles = true;

        $nonce = static::nonce($options);
        $nonce = $nonce ? "{$nonce} data-livewire-style" : '';

        $progressBarColor = config('liveview.navigate.progress_bar_color', '#2299dd');

        // Note: the attribute selectors are "doubled" so that they don't get overriden when Tailwind's CDN loads a script tag
        // BELOW the one Livewire injects...
        $html = <<<HTML
        <!-- Livewire Styles -->
        <style {$nonce}>
            [wire\:loading][wire\:loading], [wire\:loading\.delay][wire\:loading\.delay], [wire\:loading\.list-item][wire\:loading\.list-item], [wire\:loading\.inline-block][wire\:loading\.inline-block], [wire\:loading\.inline][wire\:loading\.inline], [wire\:loading\.block][wire\:loading\.block], [wire\:loading\.flex][wire\:loading\.flex], [wire\:loading\.table][wire\:loading\.table], [wire\:loading\.grid][wire\:loading\.grid], [wire\:loading\.inline-flex][wire\:loading\.inline-flex] {
                display: none;
            }

            [wire\:loading\.delay\.none][wire\:loading\.delay\.none], [wire\:loading\.delay\.shortest][wire\:loading\.delay\.shortest], [wire\:loading\.delay\.shorter][wire\:loading\.delay\.shorter], [wire\:loading\.delay\.short][wire\:loading\.delay\.short], [wire\:loading\.delay\.default][wire\:loading\.delay\.default], [wire\:loading\.delay\.long][wire\:loading\.delay\.long], [wire\:loading\.delay\.longer][wire\:loading\.delay\.longer], [wire\:loading\.delay\.longest][wire\:loading\.delay\.longest] {
                display: none;
            }

            [wire\:offline][wire\:offline] {
                display: none;
            }

            [wire\:dirty]:not(textarea):not(input):not(select) {
                display: none;
            }

            :root {
                --livewire-progress-bar-color: {$progressBarColor};
            }

            [x-cloak="x-cloak"],
            [x-cloak=""] {
                display: none !important;
            }

            [wire\:cloak="wire:cloak"],
            [wire\:cloak=""] {
                display: none !important;
            }

            dialog#livewire-error::backdrop {
                background-color: rgba(0, 0, 0, .6);
            }
        </style>
        HTML;

        return static::minify($html);
    }

    public static function scripts($options = []): string
    {
        if (app(static::class)->hasRenderedScripts) return '';

        app(static::class)->hasRenderedScripts = true;

        $debug = config('app.debug');

        $scripts = static::js($options);

        // HTML Label.
        $html = $debug ? ['<!-- Livewire Scripts -->'] : [];

        $html[] = $scripts;

        return implode("\n", $html);
    }

    public static function js($options): string
    {
        // Use the default endpoint...
        $url = url(app(static::class)->javaScriptRoute->uri());

        // Use the configured one...
        $url = config('liveview.asset_url') ?: $url;

        // Use the legacy passed in one...
        $url = $options['asset_url'] ?? $url;

        // Use the new passed in one...
        $url = $options['url'] ?? $url;

        $url = rtrim($url, '/');

        // Ensure relative URLs start with "/", but don't touch URLs with protocol schemes (e.g. "php://")...
        $url = (string) str($url)->when(
            ! str_contains($url, '://'),
            fn ($url) => $url->start('/')
        );

        // Add the build manifest hash to it...
        $manifestPath = __DIR__.'/../../../dist/manifest.json';
        $manifest = file_exists($manifestPath) ? json_decode(file_get_contents($manifestPath), true) : [];
        $versionHash = $manifest['/livewire.js'] ?? 'dev';

        // Both builds are served from the same URL, so bust the cache when switching between them...
        if (app('liveview')->isCspSafe()) {
            $versionHash .= '-csp';
        }

        $url = $url . (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . "id={$versionHash}";

        $token = app()->has('session.store') ? csrf_token() : '';

        $nonce = static::nonce($options);

        $progressBar = config('liveview.navigate.show_progress_bar', true) ? '' : 'data-no-progress-bar';

        $moduleUrl = url(app('liveview')->getUriPrefix());

        $updateUri = url(app('liveview')->getUpdateUri());

        $extraAttributes = Utils::stringifyHtmlAttributes(
            app(static::class)->scriptTagAttributes,
        );

        return <<<HTML
        <script src="{$url}" {$nonce} {$progressBar} data-csrf="{$token}" data-module-url="{$moduleUrl}" data-update-uri="{$updateUri}" {$extraAttributes}></script>
        HTML;
    }

    public static function scriptConfig($options = []): string
    {
        app(static::class)->hasRenderedScripts = true;

        $nonce = static::nonce($options);

        $progressBar = config('liveview.navigate.show_progress_bar', true) ? '' : 'data-no-progress-bar';

        $attributes = json_encode([
            'csrf' => app()->has('session.store') ? csrf_token() : '',
            'uri' => url(app('liveview')->getUpdateUri()),
            'moduleUrl' => url(app('liveview')->getUriPrefix()),
            'progressBar' => $progressBar,
            'nonce' => $options['nonce'] ?? '',
        ]);

        return <<<HTML
        <script {$nonce} data-navigate-once="true">window.livewireScriptConfig = {$attributes};</script>
        HTML;
    }

    protected static function minify($subject)
    {
        return preg_replace('~(\v|\t|\s{2,})~m', '', $subject);
    }

    protected static function nonce($options = [])
    {
        $nonce = $options['nonce'] ?? View::shared('csp_nonce');

        return $nonce ? "nonce=\"{$nonce}\"" : '';
    }
}
