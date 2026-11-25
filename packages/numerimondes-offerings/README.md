# Numerimondes Offerings PHP Package

This package is the shared offerings core used by the Webkernel website and the Perfex `nm_offerings` module. It owns offering types, classifications, validation, pricing primitives, SQLite persistence and published catalogue reads. The website-specific routes and page preparation stay in `app/src/Studio`; the Perfex module contains only the CRM UI adapter. Both applications access the same SQLite file. The package also contains the partner HTTP client described below.

This Composer package synchronizes published offers from `GET /api/v1/offerings`; authorized catalog-visible offerings can also be fetched from `GET /api/v1/offerings/{id}`. It requires PHP 8.2+ and has no runtime dependencies beyond `ext-json`.

```php
use Numerimondes\Offerings\CatalogueClient;
use Numerimondes\Offerings\SnapshotStore;

$catalogue = (new CatalogueClient(
    'https://numerimondes.com',
    getenv('NM_PARTNER_ID'),
    getenv('NM_PARTNER_TOKEN'),
    new SnapshotStore(__DIR__.'/var/numerimondes-offerings'),
))->sync();
```

Keep the bearer token in server-side environment configuration; never expose it to browser JavaScript. HTTPS is required except for localhost development. The client sends `If-None-Match`, validates the schema, writes a partner-specific snapshot atomically, and serves the last valid snapshot during API/network failures. The snapshot is for display; the central server remains authoritative for commercial quotes. Keep the storage directory outside the web root. It is created with permissions `0700`; snapshot files use `0600`.

Request a centrally priced quote after a customer selects a basket:

```php
$quote = $client->requestQuote([
    ['offering_id' => 'brand-system', 'quantity' => 2],
    ['offering_id' => 'x-brand-kit', 'quantity' => 1],
], 'EUR');
```

The client includes its configured `partner_id`; the API verifies it against the bearer identity. Requests contain one `items` array with unique `{offering_id, quantity}` entries (quantity is an integer from 1 to 1,000). Detail-only extensions are quoted only when their parent is also present and both IDs are authorized for that partner. The response includes the quote ID, partner ID, catalogue and pricing revisions, expiry, and line-level quantity/unit with customer-facing unit and extended prices. Internal partner remuneration and Numerimondes revenue are stored separately and never included in API responses. For percentage-based offers, pass structured basis values such as `['monthly_salary' => 20000]` plus the `basisCurrency` code; the central server recalculates the amount.

## Partner registry setup

Copy `config/partners.example.php` to the ignored local `config/partners.php`, replace the fictitious hash, and keep the raw token in a server-side secret manager. The example demonstrates `id`, `name`, `enabled`, SHA-256 `secret_hash`, exact allowed `domains`, `offering_ids`, customer `pricing_rules`, and internal-only `settlement_default` / `settlement_rules`. A partner must explicitly allow an extension ID to receive it nested under an authorized parent and to quote it. Settlement rules use `share_percent` (0–100); the complement of the approved partner share is recorded as Numerimondes revenue. Browser origins are an additional exact-host restriction; server-to-server requests without an Origin still require the bearer token.

Install from a private Composer repository or use this package directory as a Composer path repository. Run checks with `php tests/run.php`.

See [`examples/partner-storefront`](examples/partner-storefront/README.md) for a runnable server-rendered partner site, environment setup, outage behaviour, and an integration test against the application's actual API controllers.
