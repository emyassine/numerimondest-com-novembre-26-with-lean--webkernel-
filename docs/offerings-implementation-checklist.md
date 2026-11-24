# Offerings implementation checklist

This checklist tracks the unified offerings, partner distribution, service discovery, and view architecture requested on 2026-11-24. A requirement is complete only after its consumers are integrated and a relevant verification is recorded.

| Requirement | Implementation | Integration | Verification | Remaining work / evidence |
|---|---|---|---|---|
| Unified authoritative offering source | In progress | No | No | Migrate all mandate/service/playbook/module/revenue-force data; remove old catalogs |
| Offering types independent of billing | Partial (`OfferingType`) | No | Lint only | Connect all price models and validate |
| Relations: add-ons, extensions, maintenance, follow-up, visibility | Partial (`OfferingRelation`) | No | Lint only | Populate relationships; use in details/catalogue/shortlist |
| Industry and need taxonomies incl. Education & Training | Partial | Partial mappings | No | Audit every offering and coverage |
| Persistent editable data | No | No | No | Choose and implement DB/repository/init/backup |
| Native admin edit, draft, preview, revision, publication | No | No | No | Build complete workflow |
| Pricing components, partner overrides, FX and quote snapshots | Partial model only | No | No | Implement resolver, policies, quote persistence and validation |
| Authenticated partner API and scoped visibility/pricing | Partial auth config | No | No | Endpoint, serializer, authorization, ETag, throttling and tests |
| Partner client package/cache/sync/recovery | No | No | No | Package, atomic snapshots, ETag, cache isolation and integration |
| Complete catalogue browseable without answers | Partial selector changes | Unverified | No | Exercise running page and regressions |
| Optional industry/need and journey recommendations/reset | Partial selector changes | Unverified | No | Integrate authoritative offering source and aggregate analytics |
| Detail, related offerings, shortlist, pricing integration | No | No | No | Migrate all consumers |
| Services page partial decomposition | Partial (selector only) | Partial | No | Extract remaining page sections and data preparation |
| Webkernel native styling, responsive/accessibility/i18n | Existing UI retained | Unverified | No | Verify actual desktop/mobile/keyboard/locales/currency |
| Tests and regression verification | No | No | No | Add meaningful coverage and exercise app routes |
| Independent completion audit | No | No | No | Assign after implementation and resolve findings |

## Current checkpoint

- `Offering`, `PriceComponent`, `OfferingRelation`, `OfferingType` exist under `app/src/Studio/Catalog`.
- `Education & Training` has been added; school and auto-school modules have preliminary mappings.
- Partner bearer credential validation and an empty secure-by-default partner registry exist.
- The services selector has been extracted and made optional, with recommendations intended to leave the catalogue visible.
- The legacy catalogues still feed the running site. Therefore the unified-catalogue requirement is not complete.

## Next actions

1. Inspect and preserve all offer records and consumers; define a canonical storage schema.
2. Migrate every offer and relationship into the single source; switch all consumers and remove legacy catalogs.
3. Implement admin/publishing, pricing, partner API and client sync against that source.
4. Finish page decomposition and discovery/detail/shortlist integration.
5. Run tests and route-level checks, then independent audit.
