# Reference partner storefront

This is a runnable, server-rendered PHP example. The bearer token is read only by PHP from the process environment. The browser receives the catalogue snapshot and a CSRF token, never partner credentials. Displayed catalogue prices are indicative; submitting the form invokes the central quote API and renders its response with the quote ID and revision metadata.

## Install

From this directory, run:

```sh
composer install
```

The included Composer manifest uses the sibling `numerimondes/offerings` path package. For a deployed partner site, replace that local path repository with the private Composer repository that distributes this package.

Set environment variables in the hosting control panel or a secret manager (do not commit a local `.env`):

```sh
export NM_API_BASE_URL='https://numerimondes.com'
export NM_PARTNER_ID='example-reseller'
export NM_PARTNER_TOKEN='the-raw-token-delivered-to-this-partner'
export NM_OFFERINGS_CACHE='/srv/example-reseller/private/numerimondes-offerings'
```

The partner ID and token must match an enabled entry in Numerimondes `config/partners.php`; the stored server-side value is the SHA-256 token hash. The partner must be authorized for each parent and extension offering shown. The cache directory must be private and outside the web root. Set `NM_PARTNER_AUTOLOAD` only when Composer's autoloader is at a nonstandard location.

Run locally with PHP's development server:

```sh
php -S 127.0.0.1:8081 -t .
```

Open `http://127.0.0.1:8081`. Production must use HTTPS, a production web server, secure session cookies, and a private writable cache directory. The API client itself only permits plain HTTP for localhost development.

## Behaviour

- The page synchronizes the published catalogue through `CatalogueClient`, using its partner-isolated ETag cache and last-known-valid snapshot recovery.
- With no reachable API and no local snapshot, the page returns HTTP 503 with a useful unavailable message. With a saved snapshot, browsing continues from that snapshot; quote submission still requires the central API and shows a generic quote error if unavailable.
- Detail-only extensions are rendered under their parent. The example requires the parent checkbox to be selected with the extension; the API independently validates the relationship.
- Each selected offering has an integer quantity from 1 to 1,000; the server returns its unit and calculated extended amount.
- Quote prices and revisions come from `POST /api/v1/quotes`; the browser cannot supply or override the quoted amounts.
- The CSRF token protects the server-rendered form. Application-specific consent, lead handling, styling, and security policy should be added before production use.

Run the integration test from the repository root:

```sh
php packages/numerimondes-offerings/examples/partner-storefront/tests/integration.php
```

It uses local injected transports that call the actual Numerimondes `PartnerOfferingController` against an isolated published SQLite catalogue. It verifies nested extension synchronization, quote generation and parent linkage, and the no-snapshot outage path without requiring a network or production credentials.
