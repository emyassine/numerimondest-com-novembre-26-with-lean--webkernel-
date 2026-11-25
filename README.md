# Numerimondes website

Standalone Numerimondes Studio site built with Webkernel and Simple Site.

The Studio pages are served from the domain root. Page templates live directly
in `app/resources/views/`; reusable components are in `app/resources/components/`.
Project, capability and service-offer data live separately in
`app/resources/data/studio/projects/`, `capabilities/` and the canonical
`app/resources/data/studio/offerings.php` seed. Shared offering models, validation,
pricing and SQLite persistence live in `packages/numerimondes-offerings`; the
Perfex module supplies its admin interface, while `app/src/Studio` contains
Webkernel-specific controllers, presentation, and seed-migration utilities.
Capabilities describe the team's areas of expertise under `/capabilities`; the
service enquiry marketplace under `/services` reads the published revision of a
single SQLite-backed offerings catalogue. Staff manage offerings in the
Numerimondes Offerings module inside Perfex; deployment and partner setup are
documented in `docs/offerings-operations.md`.
Fonts are in `app/resources/assets/fonts/`.
Studio stylesheets live in `app/resources/css/studio/`; the website component
loads every CSS file in that directory from the wildcard configured in
`config/dependable-ui.php`.

Set `APP_URL` for the deployment URL. The contact page embeds the Numerimondes
ticket form hosted at `numerimondes.com`.

Service enquiry forms create Perfex leads through a server-side Perfex Connect
inbound hook. Set `PERFEX_CONNECT_LEAD_HOOK_URL` and
`PERFEX_CONNECT_LEAD_HOOK_TOKEN` in the PHP runtime environment. The hook should
target Leads, use token authentication, map `name`, `email`, `company`, `phone`
and `message`, set the desired lead defaults, and deduplicate by email. Never
put the hook token in browser code or a public config file.

## Currency display rates

Service prices are authored in MAD. Non-MAD display prices use indicative
conversions with a 2% buffer and cosmetic rounding. Refresh the private
reference-rate snapshot once a day with `php scripts/refresh-currency-rates.php`.
For cron, use a deployment-specific absolute project path, for example:

```cron
0 2 * * * cd /path/to/numerimondes && /usr/bin/php scripts/refresh-currency-rates.php >> storage/private/fx-rates.log 2>&1
```

The refresh leaves the existing snapshot untouched if the provider is
unavailable. Currencies not supplied by the provider keep their last known
configured or cached rate and show that rate's date.
# numerimondest-com-novembre-26-with-lean--webkernel-
