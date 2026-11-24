<?php declare(strict_types=1);

namespace App\Studio\Pricing;

use App\Studio\Catalog\Offering;
use App\Studio\Catalog\PriceComponent;

/** Calculates estimates from authoritative MAD catalog components and partner rules. */
final class OfferingPriceResolver
{
    /** @return list<array<string,mixed>> */
    public function resolve(Offering $offering, string $currency = 'MAD', ?PartnerPriceRule $rule = null, ?\DateTimeImmutable $at = null): array
    {
        $at ??= new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        if ($rule !== null && ($rule->partnerId === '' || $rule->offeringId !== $offering->id)) {
            throw new \InvalidArgumentException('Partner price rule does not match this offering.');
        }
        if ($rule !== null && !$rule->appliesAt($at)) $rule = null;
        $rates = CurrencyRates::forPricing()['rates'];
        $currency = strtoupper($currency);
        if (!isset($rates[$currency]) || $rates[$currency] <= 0) throw new \InvalidArgumentException('Unsupported display currency.');

        return array_map(function (PriceComponent $component) use ($rule, $rates, $currency): array {
            $base = $component->amountMad === null ? null : (int) round($component->amountMad);
            $base ??= $component->minimumMad;
            $resolved = $base === null ? null : ($rule?->resolve($base) ?? $base);
            return [
                'id' => $component->id,
                'billing' => $component->billing,
                'currency' => $currency,
                'amount' => $resolved === null ? null : round($resolved * $rates[$currency], 2),
                'minimum' => $component->minimumMad === null ? null : round(($rule?->resolve($component->minimumMad) ?? $component->minimumMad) * $rates[$currency], 2),
                'maximum' => $component->maximumMad === null ? null : round(($rule?->resolve($component->maximumMad) ?? $component->maximumMad) * $rates[$currency], 2),
                'percentage' => $component->percentage,
                'basis' => $component->basis,
                'label' => $component->label,
            ];
        }, $offering->prices);
    }
}
