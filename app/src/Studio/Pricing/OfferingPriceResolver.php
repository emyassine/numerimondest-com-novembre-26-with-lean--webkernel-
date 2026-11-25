<?php declare(strict_types=1);

namespace App\Studio\Pricing;

use App\Studio\Catalog\Offering;
use App\Studio\Catalog\PriceComponent;

/** Calculates estimates from authoritative MAD catalog components and partner rules. */
final class OfferingPriceResolver
{
    /** @param list<array<string,mixed>> $resolvedPrices */
    public static function oneTimeStartingTotal(array $resolvedPrices): float
    {
        $total = 0.0;
        foreach ($resolvedPrices as $price) {
            if (($price['billing'] ?? null) === 'one_time'
                && !is_numeric($price['percentage'] ?? null)
                && is_numeric($price['amount'] ?? null)) {
                $total += max(0, (float) $price['amount']);
            }
        }
        return round($total, 2);
    }

    /**
     * @param array<string, int|float> $basisValues Values keyed by a component's basis.value_key.
     * @param array<string, string> $basisCurrencies Input currency for each percentage basis (MAD by default).
     * @param array<string, float>|null $ratesPerMad Optional deterministic rate snapshot for tests and quotes.
     * @return list<array<string,mixed>>
     */
    public function resolve(
        Offering $offering,
        string $currency = 'MAD',
        ?PartnerPriceRule $rule = null,
        ?\DateTimeImmutable $at = null,
        array $basisValues = [],
        array $basisCurrencies = [],
        ?array $ratesPerMad = null,
        int $quantity = 1,
    ): array
    {
        if ($quantity < 1 || $quantity > 1000) throw new \InvalidArgumentException('Offering quantity must be between 1 and 1000.');
        $at ??= new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        if ($rule !== null && ($rule->partnerId === '' || $rule->offeringId !== $offering->id)) {
            throw new \InvalidArgumentException('Partner price rule does not match this offering.');
        }
        if ($rule !== null && !$rule->appliesAt($at)) $rule = null;
        $rates = $ratesPerMad ?? CurrencyRates::forPricing()['rates'];
        $currency = strtoupper($currency);
        if (!isset($rates[$currency]) || $rates[$currency] <= 0) throw new \InvalidArgumentException('Unsupported display currency.');

        foreach ($rates as $code => $rate) {
            if (!is_string($code) || !is_numeric($rate) || (float) $rate <= 0) {
                throw new \InvalidArgumentException('Currency rates must be positive amounts per MAD.');
            }
        }

        $units = array_values(array_unique(array_filter(array_map(static fn (PriceComponent $component): ?string => $component->unit, $offering->prices))));
        if (count($units) > 1) throw new \InvalidArgumentException('An offering cannot combine different quantity units.');
        if ($quantity > 1 && $units === []) throw new \InvalidArgumentException('Quantity is only supported for unit-priced offerings.');

        return array_map(function (PriceComponent $component) use ($rule, $rates, $currency, $basisValues, $basisCurrencies, $quantity): array {
            $base = $component->amountMad === null ? null : (int) round($component->amountMad);
            $basisValue = null;
            $basisCurrency = null;
            if ($component->percentage !== null) {
                $basisKey = $component->basis['value_key'] ?? (($component->basis['kind'] ?? null) === 'monthly_salary' ? 'monthly_salary' : null);
                if (is_string($basisKey) && isset($basisValues[$basisKey])) {
                    $basisValue = max(0, (float) $basisValues[$basisKey]);
                    $basisCurrency = strtoupper($basisCurrencies[$basisKey] ?? ($component->basis['currency'] ?? 'MAD'));
                    if ($basisCurrency === 'SAME_AS_SALARY') $basisCurrency = strtoupper($basisCurrencies['monthly_salary'] ?? 'MAD');
                    if (!isset($rates[$basisCurrency])) throw new \InvalidArgumentException('Unsupported percentage basis currency: '.$basisCurrency);
                    $basisMad = $basisValue / $rates[$basisCurrency];
                    $base = (int) round($basisMad * $component->percentage / 100);
                }
            }
            $base ??= $component->minimumMad;
            $resolved = $base === null ? null : ($rule?->resolve($base) ?? $base);
            $unitAmount = $resolved === null ? null : round($resolved * $rates[$currency], 2);
            $amount = $resolved === null
                ? null
                : round($resolved * ($component->unit === null ? 1 : $quantity) * $rates[$currency], 2);
            return [
                'id' => $component->id,
                'billing' => $component->billing,
                'currency' => $currency,
                'amount' => $amount,
                'unit_amount' => $component->unit !== null ? $unitAmount : null,
                'quantity' => $component->unit !== null ? $quantity : 1,
                'unit' => $component->unit,
                'minimum' => $component->minimumMad === null ? null : round(($rule?->resolve($component->minimumMad) ?? $component->minimumMad) * $rates[$currency], 2),
                'maximum' => $component->maximumMad === null ? null : round(($rule?->resolve($component->maximumMad) ?? $component->maximumMad) * $rates[$currency], 2),
                'percentage' => $component->percentage,
                'basis' => $component->basis,
                'basis_amount' => $basisValue,
                'basis_currency' => $basisCurrency,
                'label' => $component->label,
            ];
        }, $offering->prices);
    }
}
