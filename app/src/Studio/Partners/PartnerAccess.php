<?php declare(strict_types=1);

namespace App\Studio\Partners;

use Numerimondes\Offerings\Pricing\PartnerPriceRule;

/** Resolves a partner from a server-side bearer credential. */
final class PartnerAccess
{
    /** @return array{id:string, name:string, enabled:bool, secret_hash:string, domains:array, offering_ids:array, pricing_rules?:array, settlement_rules?:array, settlement_default?:array}|null */
    public static function authenticate(?string $authorization, ?string $origin): ?array
    {
        if (PHP_SAPI !== 'cli' && (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === '' || $_SERVER['HTTPS'] === 'off')) {
            return null;
        }
        if (!is_string($authorization) || !preg_match('/^Bearer\s+([A-Za-z0-9._~-]{32,256})$/i', $authorization, $match)) {
            return null;
        }

        $registryPath = base_path('config/partners.php');
        if (!is_file($registryPath)) return null;
        $partners = require $registryPath;
        if (!is_array($partners)) return null;
        foreach ($partners as $partner) {
            if (!is_array($partner)
                || ($partner['enabled'] ?? false) !== true
                || !is_string($partner['id'] ?? null)
                || !is_string($partner['secret_hash'] ?? null)
                || !preg_match('/^[a-f0-9]{64}$/', $partner['secret_hash'])
                || !is_array($partner['domains'] ?? null)
                || !is_array($partner['offering_ids'] ?? null)
                || !is_array($partner['pricing_rules'] ?? [])
                || !is_array($partner['settlement_rules'] ?? [])
                || (isset($partner['settlement_default']) && !is_array($partner['settlement_default']))) {
                continue;
            }
            if (!hash_equals((string) $partner['secret_hash'], hash('sha256', $match[1]))) {
                continue;
            }

            if (!preg_match('/^[a-z0-9][a-z0-9-]{0,79}$/', $partner['id'])
                || array_filter($partner['domains'], static fn ($domain): bool => !is_string($domain) || filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false)
                || array_filter($partner['offering_ids'], static fn ($id): bool => !is_string($id) || !preg_match('/^[a-z0-9][a-z0-9-]{0,119}$/', $id))) {
                continue;
            }
            try {
                foreach ($partner['pricing_rules'] ?? [] as $offeringId => $pricingRule) {
                    if (!is_string($offeringId) || !in_array($offeringId, $partner['offering_ids'], true) || !is_array($pricingRule)) {
                        throw new \InvalidArgumentException('Partner pricing rules must target explicitly allowed offerings.');
                    }
                    PartnerPriceRule::fromArray($partner['id'], $offeringId, $pricingRule);
                }
                if (isset($partner['settlement_default'])) PartnerSettlementPolicy::validateRule($partner['settlement_default']);
                foreach ($partner['settlement_rules'] ?? [] as $offeringId => $settlementRule) {
                    if (!is_string($offeringId) || !in_array($offeringId, $partner['offering_ids'], true) || !is_array($settlementRule)) {
                        throw new \InvalidArgumentException('Partner settlement rules must target explicitly allowed offerings.');
                    }
                    PartnerSettlementPolicy::validateRule($settlementRule);
                }
            } catch (\Throwable) {
                continue;
            }

            // Origin restrictions supplement credentials. Missing Origin is allowed for server-to-server calls.
            if ($origin !== null && $origin !== '') {
                $scheme = parse_url($origin, PHP_URL_SCHEME);
                $host = parse_url($origin, PHP_URL_HOST);
                $port = parse_url($origin, PHP_URL_PORT);
                $allowed = array_map(static fn ($domain) => is_string($domain) ? strtolower(rtrim($domain, '.')) : '', $partner['domains']);
                $normalizedHost = is_string($host) ? strtolower(rtrim($host, '.')) : '';
                $localDevelopment = in_array($normalizedHost, ['localhost', '127.0.0.1', '::1'], true);
                if (($scheme !== 'https' && !($localDevelopment && $scheme === 'http')) || $normalizedHost === '' || $port !== null || !in_array($normalizedHost, $allowed, true)) {
                    return null;
                }
            }
            return $partner;
        }
        return null;
    }
}
