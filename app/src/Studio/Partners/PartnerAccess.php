<?php declare(strict_types=1);

namespace App\Studio\Partners;

/** Resolves a partner from a server-side bearer credential. */
final class PartnerAccess
{
    /** @return array{id:string, secret_hash:string, domains:array, offering_ids:array}|null */
    public static function authenticate(?string $authorization, ?string $origin): ?array
    {
        if (!is_string($authorization) || !preg_match('/^Bearer\s+([A-Za-z0-9._~-]+)$/i', $authorization, $match)) {
            return null;
        }

        $partners = require base_path('config/partners.php');
        foreach ($partners as $partner) {
            if (!is_array($partner) || !isset($partner['id'], $partner['secret_hash'])) {
                continue;
            }
            if (!hash_equals((string) $partner['secret_hash'], hash('sha256', $match[1]))) {
                continue;
            }

            // Origin restrictions supplement credentials. Missing Origin is allowed for server-to-server calls.
            if ($origin !== null && $origin !== '') {
                $host = parse_url($origin, PHP_URL_HOST);
                $allowed = array_map('strtolower', $partner['domains'] ?? []);
                if (!is_string($host) || !in_array(strtolower($host), $allowed, true)) {
                    return null;
                }
            }
            return $partner;
        }
        return null;
    }
}
