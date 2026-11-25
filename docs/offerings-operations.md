# Offerings catalogue operations

## Initialize the catalogue

The application never creates database tables or publishes an offering catalogue during a web request. On a fresh deployment, run `php scripts/migrate-offerings-database.php` followed by `php scripts/migrate-canonical-offerings.php` before routing traffic. The canonical script also checks/creates the required schema, then imports the curated `app/resources/data/studio/offerings.php` seed and publishes its first revision. Existing deployments should run it first with `--dry-run`; it refuses to replace administrator-edited publication history or a divergent draft and creates a SQLite backup before changing an existing database. If the schema or publication is missing, application routes fail explicitly until initialization is run.

Set `NM_OFFERINGS_DATABASE` to an absolute persistent path when the default `storage/databases/offerings.sqlite` is not suitable. Keep the database directory writable by PHP-FPM and outside the document root.

## Administration

Create the administrator once with `php scripts/create-offerings-admin.php` in an interactive terminal. The command prompts for a username and password, stores only a password hash in `config/offerings-admin.php`, and restricts that file to mode `0600`. The credentials config is source controlled in this private repository; treat the hash as sensitive and rotate it with `--replace` if repository access changes. Open `/admin/offerings` locally; it redirects to the Webkernel sign-in form. Plain HTTP is allowed only when both the host and remote address are loopback (`localhost`, `127.0.0.1`, or `::1`). Outside loopback, HTTPS is required and the application's configured 403 page is rendered. The login session expires after eight hours, rotates its session ID after login, and uses CSRF tokens for state-changing requests. The editor starts from the draft when one exists, otherwise from the published snapshot.

Set `APP_ENV=prod` (or `APP_ENV=production`) for production. The admin routes are not registered there. The default environment is inferred as production for the canonical public domain and local otherwise. `is_env('prod|local')` can test aliases and multiple environments in application code.

The editor validates and saves a draft through the same server-side schema validation used for publication. Review the offering translations, prices, classifications and relationships before publication. The published revision history is append-only and is shown below the editor. Every API consumer reads the current published snapshot; drafts are never distributed.

## Partner registry

Copy `config/partners.example.php` to the ignored local file `config/partners.php`. Generate a token with `php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'`; put its SHA-256 hex digest in `secret_hash`, and store the original token only in the partner's secret manager. Keep `enabled` false until credential delivery and the partner's authorized offering IDs and domains are confirmed. The registry is intentionally absent by default, which denies partner API access.

The reference integration under `packages/numerimondes-offerings/examples/partner-storefront` demonstrates server-rendered catalogue sync, nested parent-only extensions and authoritative quote requests. Its README documents the environment variables and offline failure behaviour.

## Backups

The canonical `storage/databases/offerings.sqlite` is source controlled so a private-repository deployment can use the published catalogue snapshot. Do not commit SQLite WAL/SHM files or dated database backups. The catalogue seed remains the editable canonical source; runtime admin publications are committed through the database file.

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
