<?php declare(strict_types=1);

$GLOBALS['webkernel_request_started_at'] ??= hrtime(true);

if (! function_exists('request_time')) {
    /** Return monotonic milliseconds elapsed since the request started. */
    function request_time(): float
    {
        $started_at = $GLOBALS['webkernel_request_started_at'] ?? null;
        if (! is_int($started_at)) {
            throw new RuntimeException('The request start time is unavailable.');
        }

        return (hrtime(true) - $started_at) / 1_000_000;
    }
}

if (! function_exists('webkernel_translator')) {
    function webkernel_translator(): \Webkernel\I18n\Translator
    {
        static $translator = null;
        if ($translator instanceof \Webkernel\I18n\Translator) {
            return $translator;
        }

        $loader = new \Webkernel\I18n\FileLoader(new \Webkernel\Filesystem\Filesystem(), []);
        $packages = \Composer\InstalledVersions::getInstalledPackages();
        foreach ($packages as $package) {
            if (! str_starts_with($package, 'webkernel/')) {
                continue;
            }
            try {
                $path = \Composer\InstalledVersions::getInstallPath($package);
            } catch (OutOfBoundsException) {
                continue;
            }
            $language_path = is_string($path) ? $path.'/resources/lang' : '';
            if ($language_path !== '' && is_dir($language_path)) {
                $namespace = $package === 'webkernel/platform-support'
                    ? 'webkernel'
                    : 'webkernel-'.substr($package, strlen('webkernel/'));
                $loader->addNamespace($namespace, $language_path);
            }
        }

        $locale = \Webkernel\I18n\I18nContext::get_locale();
        return $translator = new \Webkernel\I18n\Translator($loader, $locale !== '' ? $locale : 'en');
    }
}

if (! function_exists('__')) {
    function __(string|Stringable $key, array $replace = [], ?string $locale = null): string
    {
        $key = (string) $key;
        if (str_contains($key, '::')) {
            return webkernel_translator()->get($key, $replace, $locale);
        }

        return function_exists('lang') ? lang($key, $replace, $locale) : $key;
    }
}

if (! function_exists('tmp_path')) {
    /** Resolve a temporary path inside the configured application runtime directory. */
    function tmp_path(string $path = ''): string
    {
        $temporary_path = config('paths.temporary', 'runtime/tmp');
        if (! is_string($temporary_path) || $temporary_path === '' || str_contains($temporary_path, "\0")) {
            throw new RuntimeException('The configured temporary path is invalid.');
        }

        $segments = explode('/', str_replace('\\', '/', $temporary_path));
        if (str_starts_with($temporary_path, '/') || in_array('..', $segments, true)) {
            throw new RuntimeException('The configured temporary path must stay inside the application root.');
        }

        $directory = base_path($temporary_path);
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException('Unable to create the configured temporary directory: '.$directory);
        }
        if (! is_writable($directory)) {
            throw new RuntimeException('The configured temporary directory is not writable: '.$directory);
        }

        if ($path === '') {
            return $directory;
        }

        $relative_path = str_replace('\\', '/', $path);
        if (str_starts_with($relative_path, '/') || in_array('..', explode('/', $relative_path), true)) {
            throw new InvalidArgumentException('Temporary file paths must stay inside the configured temporary directory.');
        }

        return $directory.'/'.$relative_path;
    }
}

if (! function_exists('app_url')) {
    function app_url(string $sub_path = ''): string
    {
        $configuredUrl = config('app.url');
        $base = is_string($configuredUrl) ? rtrim(trim($configuredUrl), '/') : '';
        $path = ltrim($sub_path, '/');

        if ($base === '') {
            return $path === '' ? '/' : '/'.$path;
        }

        return $path === '' ? $base.'/' : $base.'/'.$path;
    }
}

if (! function_exists('price')) {
    /** Format a price in the currently selected Numerimondes currency. */
    function price(int|float $amount, string $base_currency, ?string $target_currency = null, string $style = 'prestige'): string
    {
        $currencyConfig = config('nm-studio.currencies', []);
        $options = is_array($currencyConfig['options'] ?? null) ? $currencyConfig['options'] : [];
        $base = strtoupper($base_currency);
        $cookieName = is_string($currencyConfig['cookie'] ?? null)
            ? $currencyConfig['cookie']
            : 'numerimondes_currency';
        $default = is_string($currencyConfig['default'] ?? null) ? $currencyConfig['default'] : 'MAD';
        $target = strtoupper($target_currency ?? (is_string($_COOKIE[$cookieName] ?? null) ? $_COOKIE[$cookieName] : $default));

        if (! isset($options[$base])) {
            $base = $default;
        }
        $rateData = \App\Studio\Pricing\CurrencyRates::forPricing();
        $rates = $rateData['rates'];
        if (! isset($options[$target]) || ! isset($rates[$target], $rates[$base]) || (float) $rates[$base] <= 0) {
            $target = $base;
        }

        $converted = isset($rates[$target], $rates[$base]) && (float) $rates[$base] > 0
            ? $amount * ((float) $rates[$target] / (float) $rates[$base])
            : $amount;

        // Keep canonical/base-currency figures exact. Cosmetic rounding is only
        // for converted display figures, so the selected currency never edits offers.
        if ($target !== $base) {
            $bufferPercent = is_numeric($currencyConfig['conversion_buffer_percent'] ?? null)
                ? max(0.0, (float) $currencyConfig['conversion_buffer_percent'])
                : 0.0;
            $converted *= 1 + ($bufferPercent / 100);
            $step = match (true) {
                $converted < 1_000 => 10,
                $converted < 10_000 => $style === 'precise' ? 50 : 100,
                $converted < 100_000 => $style === 'precise' ? 250 : 500,
                default => 1_000,
            };
            $converted = round($converted / $step) * $step;
            if ($style === 'soft_charm' && $converted >= 10_000 && fmod($converted, 1_000) === 0.0) {
                $converted -= 100;
            }
        }
        $numberLocale = match ($target) {
            'USD' => 'en_US',
            'AUD' => 'en_AU',
            'NZD' => 'en_NZ',
            'AED' => 'en_AE',
            'SAR' => 'en_SA',
            'QAR' => 'en_QA',
            'GBP' => 'en_GB',
            'EUR', 'MAD' => 'fr_FR',
            'SGD' => 'en_SG',
            'HKD' => 'en_HK',
            'MYR' => 'en_MY',
            'INR' => 'en_IN',
            'CAD' => 'en_CA',
            default => 'en',
        };
        $groupingSeparator = in_array($target, ['EUR', 'MAD'], true) ? "\u{202F}" : ',';
        $formatted = \Webkernel\Utilities\Number::format($converted, 0, null, $numberLocale);

        if (! is_string($formatted)) {
            if ($target === 'INR') {
                $plain = number_format($converted, 0, '.', '');
                $negative = str_starts_with($plain, '-');
                $digits = $negative ? substr($plain, 1) : $plain;
                $lastThree = substr($digits, -3);
                $leading = substr($digits, 0, -3);
                $leading = $leading === ''
                    ? ''
                    : (preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $leading) ?? $leading).',';
                $formatted = ($negative ? '-' : '').$leading.$lastThree;
            } else {
                $formatted = number_format($converted, 0, '.', $groupingSeparator);
            }
        }

        return ($target !== $base ? '≈ ' : '').$formatted.' '.$target;
    }
}
