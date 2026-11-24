<?php declare(strict_types=1);

return [
    'path_to_perfex' => dirname(__DIR__).'/account/connect/',
    'company' => [
        'name' => 'Numerimondes SARL AU',
        'location' => 'Casablanca, Morocco',
    ],
    'appearance' => [
        'background' => '#f1f5f9',
    ],
    'currencies' => [
        'default' => 'MAD',
        'cookie' => 'numerimondes_currency',
        'show_symbol' => false,
        'conversion_buffer_percent' => 2,
        // Seed reference rates per USD. Daily Frankfurter snapshots override supported quotes.
        'rates_updated_at' => '2026-11-20',
        'per_usd' => [
            'USD' => 1.0,
            'MAD' => 9.9811,
            'EUR' => 0.909918,
            'GBP' => 0.76192,
            'AUD' => 1.50018,
            'NZD' => 1.81784,
            'CAD' => 1.43066,
            'SGD' => 1.28844,
            'HKD' => 7.83985,
            'MYR' => 4.11247,
            'INR' => 92.674,
            'AED' => 3.6695,
            'SAR' => 3.7523,
            'QAR' => 3.6427,
            'CHF' => 0.88,
        ],
        'by_country' => [
            'AU' => 'AUD', 'NZ' => 'NZD', 'AE' => 'AED', 'SA' => 'SAR', 'QA' => 'QAR',
            'GB' => 'GBP', 'IE' => 'EUR', 'FR' => 'EUR', 'CH' => 'CHF', 'SG' => 'SGD', 'HK' => 'HKD',
            'MY' => 'MYR', 'IN' => 'INR', 'MA' => 'MAD', 'ES' => 'EUR', 'GF' => 'EUR', 'CA' => 'CAD',
        ],
        'options' => [
            'USD' => ['label' => 'US Dollar', 'flag' => 'us', 'symbol' => '$'],
            'AUD' => ['label' => 'Australian Dollar', 'flag' => 'au', 'symbol' => '$'],
            'NZD' => ['label' => 'New Zealand Dollar', 'flag' => 'nz', 'symbol' => '$'],
            'AED' => ['label' => 'UAE Dirham', 'flag' => 'ae', 'symbol' => 'د.إ'],
            'SAR' => ['label' => 'Saudi Riyal', 'flag' => 'sa', 'symbol' => 'ر.س'],
            'QAR' => ['label' => 'Qatari Riyal', 'flag' => 'qa', 'symbol' => 'ر.ق'],
            'GBP' => ['label' => 'British Pound', 'flag' => 'gb', 'symbol' => '£'],
            'EUR' => ['label' => 'Euro', 'flag' => 'european_union', 'symbol' => '€'],
            'SGD' => ['label' => 'Singapore Dollar', 'flag' => 'sg', 'symbol' => '$'],
            'HKD' => ['label' => 'Hong Kong Dollar', 'flag' => 'hk', 'symbol' => '$'],
            'MYR' => ['label' => 'Malaysian Ringgit', 'flag' => 'my', 'symbol' => 'RM'],
            'INR' => ['label' => 'Indian Rupee', 'flag' => 'in', 'symbol' => '₹'],
            'MAD' => ['label' => 'Moroccan Dirham', 'flag' => 'ma', 'symbol' => 'د.م.'],
            'CAD' => ['label' => 'Canadian Dollar', 'flag' => 'ca', 'symbol' => '$'],
            'CHF' => ['label' => 'Swiss Franc', 'flag' => 'ch', 'symbol' => 'CHF'],
        ],
    ],
    'contact' => [
        'phone' => [
            'display' => '+212 620 990 692',
            'href' => 'tel:+212620990692',
        ],
        'whatsapp' => 'https://wa.me/212620990692?text=Hello%20Numerimondes%2C%20I%27d%20like%20to%20get%20in%20touch.',
        'form' => [
            'embed' => 'https://numerimondes.com/account/connect/forms/ticket?styled=1&language=english&col=col-md-8+col-md-offset-2',
            'direct' => 'https://numerimondes.com/account/connect/forms/ticket?styled=1',
        ],
    ],
    'crm' => [
        'client_space' => 'https://numerimondes.com/account/connect/authentication/login',
        'client_registration' => 'https://numerimondes.com/account/connect/authentication/register',
        'client_request' => 'https://numerimondes.com/account/connect/clients/open_ticket',
        'staff_login' => 'https://numerimondes.com/account/connect/admin/authentication',
    ],
    'perfex' => [
        'lead_hook_url' => getenv('PERFEX_CONNECT_LEAD_HOOK_URL') ?: '',
        'lead_hook_token' => getenv('PERFEX_CONNECT_LEAD_HOOK_TOKEN') ?: '',
    ],
    'social' => [
        'LinkedIn' => 'https://www.linkedin.com/in/elmoumenyassine/',
        'Instagram' => 'https://www.instagram.com/numerimondes/',
        'X' => 'https://x.com/elmoumenyassine',
        'WhatsApp' => 'https://wa.me/212620990692',
    ],
];
