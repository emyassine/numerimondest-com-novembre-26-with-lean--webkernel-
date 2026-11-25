<?php declare(strict_types=1);

/**
 * Copy to config/partners.php and replace the illustrative credential hash.
 * Generate a partner token with: php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
 * Store only hash('sha256', $token) here; deliver the token through a secret manager.
 */
return [
    [
        'id' => 'example-reseller',
        'name' => 'Example Reseller (fictional)',
        'enabled' => false,
        'secret_hash' => 'f0e689d6dbfb0ad9c6798193bf2e55c7466e13be8aae50320ef1d0c19319555d', // Fictitious example token hash; replace before enabling.
        'domains' => ['shop.example.test'],
        'offering_ids' => ['brand-system', 'dig-flagship', 'x-brand-kit'],
        'pricing_rules' => [
            // Example: apply a time-bounded 5% customer-price increase to one offering.
            'brand-system' => [
                'mode' => 'adjust_percent',
                'adjustment_percent' => 5,
                'minimum_mad' => 1000,
                'maximum_mad' => 250000,
                'starts_at' => '2026-01-01T00:00:00Z',
                'ends_at' => '2026-12-31T23:59:59Z',
            ],
            // Extensions are authorized individually for nested display and quoting, not standalone listing.
            'x-brand-kit' => ['mode' => 'inherit'],
        ],
        // Internal settlement is stored separately from customer prices and never returned by the API.
        'settlement_default' => ['mode' => 'share_percent', 'partner_share_percent' => 12.5],
        'settlement_rules' => [
            // Optional per-offering override; the remainder is Numerimondes revenue.
            'brand-system' => ['mode' => 'share_percent', 'partner_share_percent' => 15],
        ],
    ],
];
