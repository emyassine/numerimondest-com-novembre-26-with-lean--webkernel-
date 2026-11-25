# Services catalogue

The services page is driven by one revisioned offerings catalogue. Its canonical
seed is `app/resources/data/studio/offerings.php`; after initialization, published
and draft data are stored in the SQLite database configured by
`NM_OFFERINGS_DATABASE` (default `storage/databases/offerings.sqlite`). Runtime
routes never seed or fall back to PHP catalogue arrays.

The catalogue contains 16 active mandates, a separate Revenue Force offer, three
vertical software modules, five executive playbooks, and parent-linked detail-only
extensions. The 20 extension prices remain provisional pending owner validation.
The active catalogue cards, details, recommendations, shortlist, inquiry quotes,
administration editor and partner API all resolve the same published revision.

## Operations

See [Offerings catalogue operations](offerings-operations.md) for first-time
initialization, guarded data migration, administration, partner setup, backups,
and currency-rate refresh.

The interactive filter continues to show the full catalogue while the industry,
business-need and optional business-journey selectors populate a separate
recommendations area. Selector analytics contain daily aggregate counts only.
Partner distribution uses the authenticated API and client package under
`packages/numerimondes-offerings/`.

## Pending business decisions

- Extension amounts were supplied as placeholders and need approval.
- Confirm partner-specific price overrides and settlement shares per commercial
  agreement before enabling a partner registry entry.
- Add the daily FX refresh command to the production HestiaCP schedule.
- Configure the production Perfex lead-hook URL and secret in the PHP-FPM
  environment before accepting live service inquiries.
- Run a representative production partner integration after the partner's
  credentials and allowed offering list are provisioned.
