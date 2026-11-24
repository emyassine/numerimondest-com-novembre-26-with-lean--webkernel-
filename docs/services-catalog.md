# Services catalogue implementation

The services page follows `html/commissions.html`. Its catalogue, extensions,
Executive Playbooks, vertical modules and Revenue Force figures are stored in
`app/src/Studio/MandateCatalog.php`; client-side code only filters and renders
these server-provided values. This repository currently has no CMS collection
or database migrations for this catalogue, so the PHP data provider preserves
the site's existing server-owned data pattern. It is the main deviation from
the requested CMS/database model.

## Assumptions and open questions

- The prototype has 16 catalog mandates and a separate Revenue Force block. The
  page therefore filters 16 mandates; together with Revenue Force it presents
  17 main offers.
- Recruitment mandates retain their prototype labels and are treated as actual
  recruitment services, not as deployable concepts.
- All 20 extension prices are the supplied placeholders and need approval.
- Currency conversions use the existing private Frankfurter snapshot and the
  existing daily command, `php scripts/refresh-currency-rates.php`. Add the
  documented cron entry on the production host if it is not already configured.
- Subscription cards add the selected module to the private shortlist as the
  prototype does. A separate waitlist form/storage destination has not been
  specified.
- No analytics destination is configured in this site, so the requested
  privacy-friendly event names are not sent to an analytics service.
- The prototype's modal detail view is retained; extensions have no standalone
  routes. The existing enquiry session carries their IDs and the lead text now
  includes the parent mandate.
- Lighthouse scores have not been measured as part of this change. The local
  `platform server` command cannot start in the current sandbox because the
  locked `esbuild` install fails with `spawnSync … EPERM`.
