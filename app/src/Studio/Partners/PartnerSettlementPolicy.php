<?php declare(strict_types=1);

namespace App\Studio\Partners;

/** Resolves internal partner economics separately from customer-facing prices. */
final class PartnerSettlementPolicy
{
    /** @return array{partner_remuneration_mad:float,numerimondes_revenue_mad:float} */
    public static function resolve(array $partner, string $offeringId, float $customerPriceMad): array
    {
        $rule = $partner['settlement_rules'][$offeringId] ?? $partner['settlement_default'] ?? ['mode' => 'share_percent', 'partner_share_percent' => 0];
        if (!is_array($rule) || ($rule['mode'] ?? null) !== 'share_percent' || !is_numeric($rule['partner_share_percent'] ?? null)) {
            throw new \InvalidArgumentException('Partner settlement rule is invalid.');
        }
        $percent = (float) $rule['partner_share_percent'];
        if (!is_finite($percent) || $percent < 0 || $percent > 100 || !is_finite($customerPriceMad) || $customerPriceMad < 0) {
            throw new \InvalidArgumentException('Partner settlement values are outside allowed limits.');
        }
        $partnerRemuneration = round($customerPriceMad * $percent / 100, 2);
        return ['partner_remuneration_mad' => $partnerRemuneration, 'numerimondes_revenue_mad' => round($customerPriceMad - $partnerRemuneration, 2)];
    }

    /** @param array<string,mixed> $rule */
    public static function validateRule(array $rule): void
    {
        if (array_diff(array_keys($rule), ['mode', 'partner_share_percent']) !== []
            || ($rule['mode'] ?? null) !== 'share_percent'
            || !is_numeric($rule['partner_share_percent'] ?? null)
            || !is_finite((float) $rule['partner_share_percent'])
            || (float) $rule['partner_share_percent'] < 0
            || (float) $rule['partner_share_percent'] > 100) {
            throw new \InvalidArgumentException('Settlement rules require a share_percent from 0 to 100.');
        }
    }
}
