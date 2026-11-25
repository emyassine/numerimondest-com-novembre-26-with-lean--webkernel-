<?php declare(strict_types=1);

namespace App\Studio\Pricing;

use RuntimeException;

/** Reads and refreshes the public reference-rate snapshot used for display prices. */
final class CurrencyRates
{
    private const API_URL = 'https://api.frankfurter.dev/v2/rates?base=MAD';

    /** @return array{rates: array<string, float>, dates: array<string, string>, checked_at: array<string, string>, sources: array<string, string>} */
    public static function forPricing(): array
    {
        static $cached = null;
        if (is_array($cached)) {
            return $cached;
        }

        $config = self::config();
        $currencyConfig = $config['currencies'] ?? [];
        $fallbackUsd = is_array($currencyConfig['per_usd'] ?? null) ? $currencyConfig['per_usd'] : [];
        $usdMad = (float) ($fallbackUsd['MAD'] ?? 0);
        $rates = ['MAD' => 1.0];
        $dates = ['MAD' => ''];
        $checkedAt = ['MAD' => ''];
        $sources = ['MAD' => 'base currency'];

        foreach (($currencyConfig['options'] ?? []) as $currency => $_option) {
            if ($currency === 'MAD') {
                continue;
            }

            $rate = $usdMad > 0 && isset($fallbackUsd[$currency])
                ? (float) $fallbackUsd[$currency] / $usdMad
                : 0.0;
            if ($rate > 0) {
                $rates[$currency] = $rate;
                $dates[$currency] = (string) ($currencyConfig['rates_updated_at'] ?? '');
                $checkedAt[$currency] = '';
                $sources[$currency] = 'configured reference rate';
            }
        }

        $snapshot = self::readSnapshot();
        foreach (($snapshot['rates_per_mad'] ?? []) as $currency => $rate) {
            if (is_string($currency) && is_numeric($rate) && (float) $rate > 0) {
                $rates[$currency] = (float) $rate;
                $dates[$currency] = (string) ($snapshot['rate_dates'][$currency] ?? '');
                $checkedAt[$currency] = (string) ($snapshot['rate_checked_at'][$currency] ?? '');
                $sources[$currency] = (string) ($snapshot['rate_sources'][$currency] ?? 'Frankfurter reference rates');
            }
        }

        $cached = ['rates' => $rates, 'dates' => $dates, 'checked_at' => $checkedAt, 'sources' => $sources];

        return $cached;
    }

    /** @return array{rate_date: string, checked_at: string, source: string} */
    public static function displayMetadata(string $currency): array
    {
        $data = self::forPricing();
        $currency = strtoupper($currency);

        return [
            'rate_date' => $data['dates'][$currency] ?? '',
            'checked_at' => $data['checked_at'][$currency] ?? '',
            'source' => $data['sources'][$currency] ?? 'reference rate unavailable',
        ];
    }

    /**
     * Keep the default currency first, then sort the rest strongest first.
     *
     * Rates are quoted as units of the selected currency per MAD, so a lower
     * quote represents a stronger currency. Currencies with no known rate are
     * kept at the end in their configured order.
     *
     * @param array<string, array<string, mixed>> $options
     * @return array<string, array<string, mixed>>
     */
    public static function strongestFirst(array $options, ?string $defaultCurrency = null): array
    {
        $rates = self::forPricing()['rates'];
        $configuredOrder = array_flip(array_keys($options));
        $currencies = array_keys($options);

        usort($currencies, static function (string $left, string $right) use ($rates, $configuredOrder, $defaultCurrency): int {
            if ($left === $right) {
                return 0;
            }
            if ($left === $defaultCurrency) {
                return -1;
            }
            if ($right === $defaultCurrency) {
                return 1;
            }
            $leftRate = isset($rates[$left]) && $rates[$left] > 0 ? (float) $rates[$left] : INF;
            $rightRate = isset($rates[$right]) && $rates[$right] > 0 ? (float) $rates[$right] : INF;

            return ($leftRate <=> $rightRate) ?: ($configuredOrder[$left] <=> $configuredOrder[$right]);
        });

        $ordered = [];
        foreach ($currencies as $currency) {
            $ordered[$currency] = $options[$currency];
        }

        return $ordered;
    }

    /** Fetch once for cron; missing quote currencies keep their last configured/snapshot value. */
    public static function refresh(): array
    {
        $config = self::config();
        $currencyConfig = $config['currencies'] ?? [];
        $options = is_array($currencyConfig['options'] ?? null) ? $currencyConfig['options'] : [];
        $quotes = array_values(array_filter(array_keys($options), static fn ($currency): bool => $currency !== 'MAD'));
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 8,
                'ignore_errors' => true,
                'header' => "Accept: application/json\r\nUser-Agent: Numerimondes-Pricing/1.0\r\n",
            ],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);
        $body = @file_get_contents(self::API_URL, false, $context);
        $status = $http_response_header[0] ?? '';
        if (! is_string($body) || $body === '' || preg_match('/\s2\d\d\s/', $status) !== 1) {
            throw new RuntimeException('FX provider request failed; existing rates were left unchanged.');
        }

        $rows = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($rows) || $rows === []) {
            throw new RuntimeException('FX provider returned no usable rates; existing rates were left unchanged.');
        }

        $snapshot = self::readSnapshot();
        $rates = is_array($snapshot['rates_per_mad'] ?? null) ? $snapshot['rates_per_mad'] : [];
        $dates = is_array($snapshot['rate_dates'] ?? null) ? $snapshot['rate_dates'] : [];
        $checkedAt = is_array($snapshot['rate_checked_at'] ?? null) ? $snapshot['rate_checked_at'] : [];
        $sources = is_array($snapshot['rate_sources'] ?? null) ? $snapshot['rate_sources'] : [];
        $updated = [];
        $refreshedAt = gmdate('Y-m-d\TH:i:s\Z');

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $quote = strtoupper((string) ($row['quote'] ?? ''));
            $rate = $row['rate'] ?? null;
            $date = $row['date'] ?? null;
            if (strtoupper((string) ($row['base'] ?? '')) !== 'MAD'
                || ! in_array($quote, $quotes, true)
                || ! is_numeric($rate)
                || (float) $rate <= 0
                || ! is_string($date)
                || ! self::isDate($date)
            ) {
                continue;
            }
            $rates[$quote] = (float) $rate;
            $dates[$quote] = $date;
            $checkedAt[$quote] = $refreshedAt;
            $sources[$quote] = 'Frankfurter reference rates';
            $updated[] = $quote;
        }

        if ($updated === []) {
            throw new RuntimeException('FX provider response did not contain supported quotes; existing rates were left unchanged.');
        }

        $payload = [
            'base_currency' => 'MAD',
            'provider' => 'Frankfurter API v2',
            'refreshed_at_utc' => $refreshedAt,
            'updated_currencies' => array_values(array_unique($updated)),
            'rates_per_mad' => $rates,
            'rate_dates' => $dates,
            'rate_checked_at' => $checkedAt,
            'rate_sources' => $sources,
        ];
        self::writeSnapshot($payload);

        return $payload;
    }

    /** @return array<string, mixed> */
    private static function config(): array
    {
        $path = base_path('/config/nm-studio.php');
        $config = is_file($path) ? require $path : [];

        return is_array($config) ? $config : [];
    }

    /** @return array<string, mixed> */
    private static function readSnapshot(): array
    {
        $path = base_path('/storage/private/pricing/currency-rates.json');
        if (! is_file($path)) {
            return [];
        }
        $contents = @file_get_contents($path);
        if (! is_string($contents)) {
            return [];
        }
        $data = json_decode($contents, true);

        return is_array($data) && ($data['base_currency'] ?? null) === 'MAD' ? $data : [];
    }

    /** @param array<string, mixed> $payload */
    private static function writeSnapshot(array $payload): void
    {
        $directory = base_path('/storage/private/pricing');
        if (! is_dir($directory) && ! @mkdir($directory, 0770, true) && ! is_dir($directory)) {
            throw new RuntimeException('Unable to create the private FX cache directory.');
        }
        $path = $directory.'/currency-rates.json';
        $temporary = $path.'.'.bin2hex(random_bytes(6)).'.tmp';
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;
        if (file_put_contents($temporary, $json, LOCK_EX) === false || ! @rename($temporary, $path)) {
            @unlink($temporary);
            throw new RuntimeException('Unable to publish FX snapshot; existing rates were left unchanged.');
        }
        @chmod($path, 0660);
    }

    private static function isDate(string $date): bool
    {
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }
}
