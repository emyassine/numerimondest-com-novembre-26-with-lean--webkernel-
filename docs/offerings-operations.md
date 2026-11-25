# Offerings catalogue operations

## Initialize the catalogue

The application never creates database tables or publishes an offering catalogue during a web request. On a fresh deployment, run `php scripts/migrate-offerings-database.php` followed by `php scripts/migrate-canonical-offerings.php` before routing traffic. The canonical script also checks/creates the required schema, then imports the curated `app/resources/data/studio/offerings.php` seed and publishes its first revision. Existing deployments should run it first with `--dry-run`; it refuses to replace administrator-edited publication history or a divergent draft and creates a SQLite backup before changing an existing database. If the schema or publication is missing, application routes fail explicitly until initialization is run.

Set `NM_OFFERINGS_DATABASE` to an absolute persistent path when the default `storage/databases/offerings.sqlite` is not suitable. Keep the database directory writable by PHP-FPM and outside the document root.

## Administration

The Perfex module at `account/modules/nm_offerings` is the staff interface. Activate **Numerimondes Offerings** from Perfex's Setup → Modules, then grant staff the `view`, `edit`, and `publish` capabilities as appropriate. It uses Perfex staff authentication and CSRF-protected forms; there is no separate Numerimondes admin password or login route.

The module opens the same `storage/databases/offerings.sqlite` file used by the website. It registers an `App\Studio`-scoped class loader for the shared catalogue classes, validates changes with `OfferingCatalogValidator`, saves drafts and publishes revisions through `OfferingStore`, and logs the staff ID for each change. It does not load the Webkernel runtime or store catalogue data in Perfex's CRM database. The PHP process serving Perfex must have read/write access to the SQLite file and write access to its directory for SQLite WAL/SHM files. Keep this database outside the document root and back up the SQLite file using SQLite's online backup mechanism below.

The module lists catalogue records and edits one offering JSON object at a time, with a create flow for new IDs. It validates the complete resulting catalogue, including translations, taxonomies, prices, and relationships, before saving; publishing revalidates the saved draft. The current published snapshot and append-only publication history continue to serve the website and partner API; drafts are not distributed. Other Perfex modules can retrieve published records with `nm_offerings_all()` or a specific record with `nm_offerings_find('offering-id')` after loading `nm_offerings/nm_offerings` helper.

## Partner registry

Copy `config/partners.example.php` to the ignored local file `config/partners.php`. Generate a token with `php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'`; put its SHA-256 hex digest in `secret_hash`, and store the original token only in the partner's secret manager. Keep `enabled` false until credential delivery and the partner's authorized offering IDs and domains are confirmed. The registry is intentionally absent by default, which denies partner API access.

The reference integration under `packages/numerimondes-offerings/examples/partner-storefront` demonstrates server-rendered catalogue sync, nested parent-only extensions and authoritative quote requests. Its README documents the environment variables and offline failure behaviour.

## Backups

The canonical `storage/databases/offerings.sqlite` is source controlled so a private-repository deployment can use the published catalogue snapshot. Do not commit SQLite WAL/SHM files or dated database backups. The canonical seed is the initial catalogue source; Perfex publications are written to this same database file.

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
