# Service market fit

The Services catalogue keeps the current offer arrays for now. Each service and mandate receives two normalized fields from `App\Studio\Catalog\Taxonomy\OfferMarketFit`:

- `industries`: `Industry` enum values.
- `business_needs`: `BusinessNeed` enum values.

The mapping is deliberately separate from the offer copy so the existing catalogue format can be revised later without duplicating taxonomy definitions. Extensions inherit their parent mandate’s mapping. Playbooks inherit the mapping of their gateway mandate. The selector uses these same fields to filter existing mandate cards.

Industry and business need counts for gap analysis can be produced with:

```php
$offers = App\Studio\Catalog\MandateCatalog::data()['mandates'];
$coverage = App\Studio\Catalog\Taxonomy\OfferMarketFit::coverage($offers);
```

Mappings are an initial editorial classification to review against delivery experience. The supplied industry taxonomy has no Education category, so the driving-school and private-school modules intentionally have no industry assignment; Bookshops & Trade maps to Retail. This gap remains visible in the coverage report instead of being hidden behind an invented category.

## Anonymous selector counts

Submitting the two-question selector writes only a daily aggregate count for the industry and business-need pair to `runtime/telemetry/service-market-fit.json`. It stores no name, email, IP address, user agent, shortlist, or individual selection history. Counts older than 400 days are removed when a new selection is recorded. If telemetry storage is unavailable, recommendations still work and the page continues normally.

## Studio PHP layout

- `Catalog/`: service, mandate and project data.
- `Catalog/Taxonomy/`: industry and business-need enums and offer mappings.
- `Http/`: route controllers.
- `Integration/Perfex/`: Perfex connection boundary.
- `Pricing/`: display currency rates.
- `Shortlist/`: shortlist persistence.
- `Analytics/`: aggregate, non-identifying selector counts.
