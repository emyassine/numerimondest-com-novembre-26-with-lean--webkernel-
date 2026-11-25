<?php declare(strict_types=1);

$GLOBALS['webkernel_request_started_at'] ??= hrtime(true);

if (! function_exists('is_env')) {
    /** Check the current application environment; supports pipe-separated names such as "prod|staging". */
    function is_env(string $environments): bool
    {
        $current = strtolower(trim((string) config('app.env', 'production')));
        $aliases = ['prod' => 'production', 'dev' => 'development'];
        $current = $aliases[$current] ?? $current;
        foreach (explode('|', strtolower($environments)) as $environment) {
            $environment = trim($environment);
            $environment = $aliases[$environment] ?? $environment;
            if ($environment !== '' && hash_equals($environment, $current)) return true;
        }
        return false;
    }
}

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

if (! function_exists('__t')) {
    /** Resolve a localized content map at the presentation boundary. */
    function __t(array|string|null $value, ?string $locale = null): string
    {
        if (is_string($value)) {
            return $value;
        }
        if ($value === null || $value === []) {
            return '';
        }
        $locale ??= \Webkernel\I18n\I18nContext::get_locale();
        if ($locale !== '' && is_string($value[$locale] ?? null)) {
            return $value[$locale];
        }
        if (is_string($value['en'] ?? null)) {
            return $value['en'];
        }
        foreach ($value as $translation) {
            if (is_string($translation)) {
                return $translation;
            }
        }
        return '';
    }
}

if (! function_exists('offering_price_components')) {
    /** @return list<array<string,mixed>> */
    function offering_price_components(array $record, ?string $currency = null): array
    {
        $settings = config('nm-studio.currencies', []);
        $options = is_array($settings['options'] ?? null) ? $settings['options'] : [];
        $cookie = is_string($settings['cookie'] ?? null) ? $settings['cookie'] : 'numerimondes_currency';
        $selected = strtoupper($currency ?? (is_string($_COOKIE[$cookie] ?? null) ? $_COOKIE[$cookie] : (string)($settings['default'] ?? 'MAD')));
        if (!isset($options[$selected])) $selected = 'MAD';
        return (new \Numerimondes\Offerings\Pricing\OfferingPriceResolver())->resolve(
            \Numerimondes\Offerings\Catalog\Offering::fromArray($record),
            $selected,
            ratesPerMad: \App\Studio\Pricing\CurrencyRates::forPricing()['rates'],
        );
    }
}

if (! function_exists('offering_price_display')) {
    function offering_price_display(array $record, ?string $currency = null): string
    {
        $components = offering_price_components($record, $currency);
        $parts = [];
        foreach ($components as $component) {
            $label = __t($component['label'] ?? []);
            $percentage = is_numeric($component['percentage'] ?? null) ? (float)$component['percentage'] : null;
            $amount = is_numeric($component['amount'] ?? null) ? (float)$component['amount'] : null;
            $minimum = is_numeric($component['minimum'] ?? null) ? (float)$component['minimum'] : null;
            $maximum = is_numeric($component['maximum'] ?? null) ? (float)$component['maximum'] : null;
            $unit = strtoupper((string)($component['currency'] ?? $currency ?? 'MAD'));
            if ($percentage !== null) {
                $basisKind = (string)($component['basis']['kind'] ?? '');
                $basisKey = (string)($component['basis']['value_key'] ?? '');
                $basisLabel = $basisKind === 'monthly_salary' || $basisKey === 'monthly_salary' ? 'monthly salary' : 'the stated basis';
                $percentageText = number_format($percentage, 0).'% of '.$basisLabel;
                $part = $label !== '' && stripos($label, $percentageText) !== false
                    ? $label
                    : $percentageText.($label !== '' ? ' · '.$label : '');
                if ($amount !== null) $part .= ' · '.price($amount, $unit, $unit);
            } elseif ($minimum !== null && $maximum !== null) {
                $range = price($minimum, $unit, $unit).'–'.price($maximum, $unit, $unit);
                $part = $label !== '' ? $label.' · '.$range : 'From '.$range;
            } elseif ($amount !== null) {
                $value = price($amount, $unit, $unit);
                $part = $label !== '' ? $label.' · '.$value : 'From '.$value;
            } else {
                $part = $label;
            }
            $part .= match ($component['billing'] ?? '') {
                'monthly' => ' / month', 'quarterly' => ' / quarter', 'annual' => ' / year', default => '',
            };
            if ($part !== '') $parts[] = $part;
        }
        return implode(' + ', $parts);
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
