<?php

use Webkernel\Platform\Contracts\Plugin;
use Webkernel\Platform\PlatformManager;
use Webkernel\Filesystem\Filesystem;
use Webkernel\I18n\FileLoader;
use Webkernel\I18n\I18nContext;
use Webkernel\I18n\Translator;

if (! function_exists('platform')) {
    /**
     * @return ($plugin is null ? PlatformManager : Plugin)
     */
    function platform(?string $plugin = null): PlatformManager | Plugin
    {
        // The panel serving this process is authoritative: a Platform layout may
        // render through a view engine that does not share the request container.
        $platform = PlatformManager::activeManager() ?? app('platform');

        if ($plugin !== null) {
            return $platform->getPlugin($plugin);
        }

        return $platform;
    }
}

if (! function_exists('webkernel_translator')) {
    /** Builds the namespace-aware translator used by Platform translation keys. */
    function webkernel_translator(): Translator
    {
        static $translator = null;

        if ($translator instanceof Translator) {
            return $translator;
        }

        $loader = new FileLoader(new Filesystem, []);

        foreach ([
            'webkernel-platform' => __DIR__ . '/../resources/lang',
            'webkernel-actions' => __DIR__ . '/../../actions/resources/lang',
            'webkernel-forms' => __DIR__ . '/../../forms/resources/lang',
            'webkernel-infolists' => __DIR__ . '/../../infolists/resources/lang',
            'webkernel-notifications' => __DIR__ . '/../../notifications/resources/lang',
            'webkernel-schemas' => __DIR__ . '/../../schemas/resources/lang',
            'webkernel-tables' => __DIR__ . '/../../tables/resources/lang',
            'webkernel-widgets' => __DIR__ . '/../../widgets/resources/lang',
            'webkernel-query-builder' => __DIR__ . '/../../query-builder/resources/lang',
            'webkernel' => __DIR__ . '/../../support/resources/lang',
        ] as $namespace => $path) {
            $loader->addNamespace($namespace, $path);
        }

        $locale = I18nContext::get_locale();

        return $translator = new Translator($loader, $locale !== '' ? $locale : 'en');
    }
}

if (! function_exists('__')) {
    function __(string|\Stringable $key, array $replace = [], ?string $locale = null): string
    {
        $key = (string) $key;

        if (str_contains($key, '::')) {
            return webkernel_translator()->get($key, $replace, $locale);
        }

        return function_exists('lang') ? lang($key, $replace, $locale) : $key;
    }
}

if (! function_exists('trans')) {
    function trans(string|\Stringable $key, array $replace = [], ?string $locale = null): string
    {
        return __($key, $replace, $locale);
    }
}

if (! function_exists('trans_choice')) {
    function trans_choice(string|\Stringable $key, $number, array $replace = [], ?string $locale = null): string
    {
        $key = (string) $key;

        if (str_contains($key, '::')) {
            return webkernel_translator()->choice($key, $number, $replace, $locale);
        }

        return function_exists('lang') ? lang($key, $replace, $locale) : $key;
    }
}
