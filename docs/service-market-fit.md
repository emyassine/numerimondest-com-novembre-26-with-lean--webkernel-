# Service market fit

The unified offering records in the published catalogue carry `industries` and
`business_needs` directly. `Industry` and `BusinessNeed` enums define the valid
values; the administrator validates assignments before a draft can be saved or
published. Education & Training is included for driving-school and private-school
software modules.

The selector recommends from the same published catalogue used by the services
page, detail routes, shortlist, inquiry quote snapshots and partner API.
Offerings with broad sector applicability explicitly list their supported
industries; an empty industry list is not interpreted as universal applicability.
Extensions inherit their context from their parent relationship and do not appear
as standalone catalogue recommendations.

Example:

```php
$offerings = App\Studio\Catalog\OfferingCatalog::all();
$recommendations = App\Studio\Catalog\MarketFitRecommender::recommend(
    'healthcare-life-sciences',
    'operations',
    ['team_size' => '11-50'],
);
```

## Anonymous selector counts

Selector submissions write daily aggregate counts only to
`runtime/telemetry/service-market-fit.json`. No name, email, IP address, user
agent, shortlist, or individual selection history is stored. Counts older than
400 days are removed on the next write. Recommendations continue to work if
telemetry storage is unavailable.

## Studio PHP layout

- `Catalog/`: offering model, relationships, recommender and taxonomies.
- `Admin/`: catalogue draft, preview and publication endpoints.
- `Persistence/`: revisioned offerings and immutable quote snapshots.
- `Pricing/`: canonical MAD pricing, partner rules and display currency rates.
- `Partners/` and `Http/`: distribution authorization and API controllers.
- `Presentation/`: services page data preparation.
- `Analytics/`: aggregate, non-identifying selector counts.
- `Shortlist/`: session-persisted offering IDs.
