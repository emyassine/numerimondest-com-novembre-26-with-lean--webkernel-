# Offerings catalogue operations

## Initialize the catalogue

The application never creates database tables or publishes an offering catalogue during a web request. On a fresh deployment, run `php scripts/migrate-offerings-database.php` followed by `php scripts/migrate-canonical-offerings.php` before routing traffic. The canonical script also checks/creates the required schema, then imports the curated `app/resources/data/studio/offerings.php` seed and publishes its first revision. Existing deployments should run it first with `--dry-run`; it refuses to replace administrator-edited publication history or a divergent draft and creates a SQLite backup before changing an existing database. If the schema or publication is missing, application routes fail explicitly until initialization is run.

Set `NM_OFFERINGS_DATABASE` to an absolute persistent path when the default `storage/databases/offerings.sqlite` is not suitable. Keep the database directory writable by PHP-FPM and outside the document root.

## Administration

Set `NM_OFFERINGS_ADMIN_TOKEN` to a random secret of at least 32 characters in the PHP-FPM environment. Open `/admin/offerings` over HTTPS. The browser requests HTTP Basic authentication; use `admin` as the username and the environment token as the password. The editor starts from the draft when one exists, otherwise from the published snapshot.

The editor validates and saves a draft through the same server-side schema validation used for publication. Review the offering translations, prices, classifications and relationships before publication. The published revision history is append-only and is shown below the editor. Every API consumer reads the current published snapshot; drafts are never distributed.

## Partner registry

Copy `config/partners.example.php` to the ignored local file `config/partners.php`. Generate a token with `php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'`; put its SHA-256 hex digest in `secret_hash`, and store the original token only in the partner's secret manager. Keep `enabled` false until credential delivery and the partner's authorized offering IDs and domains are confirmed. The registry is intentionally absent by default, which denies partner API access.

The reference integration under `packages/numerimondes-offerings/examples/partner-storefront` demonstrates server-rendered catalogue sync, nested parent-only extensions and authoritative quote requests. Its README documents the environment variables and offline failure behaviour.

## Backups

Back up the SQLite database using SQLite's online backup command so the WAL is checkpointed consistently:

```sh
mkdir -p storage/backups
sqlite3 storage/databases/offerings.sqlite ".backup 'storage/backups/offerings-$(date -u +%Y%m%dT%H%M%SZ).sqlite'"
```

Restrict backup permissions and copy them to the organization's encrypted backup location. Test restores on a separate database path before relying on a backup. Database files and backup files are excluded from version control.

## Existing catalogue data migration

For an installation with a previously published catalogue, run `php scripts/migrate-canonical-offerings.php --dry-run` and inspect the result. The migration refuses non-seed publication history, divergent drafts, and unknown offering IDs rather than replacing data it cannot prove safe to refresh. It marks replaced standalone services inactive and hidden while preserving their stored business content. Run it without `--dry-run` only after the preview succeeds.

## Indicative currency rates

Schedule `php scripts/refresh-currency-rates.php` once daily from the HestiaCP cron interface. The task downloads the supported MAD reference quotes and atomically replaces `storage/private/pricing/currency-rates.json`; failed refreshes leave the last valid snapshot and configured fallback rates intact. The page keeps the indicative-rate disclaimer and reports the source date.
