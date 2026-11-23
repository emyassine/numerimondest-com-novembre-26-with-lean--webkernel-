@php
    $allServices = \App\Studio\ServiceCatalog::all();
    $servicesUrl = simple_site('studio', 'services');
    $currencyConfig = config('nm-studio.currencies', []);
    $currencyOptions = is_array($currencyConfig['options'] ?? null) ? $currencyConfig['options'] : [];
    $currencyCookie = (string) ($currencyConfig['cookie'] ?? 'numerimondes_currency');
    $displayCurrency = is_string($_COOKIE[$currencyCookie] ?? null) && isset($currencyOptions[$_COOKIE[$currencyCookie]])
        ? $_COOKIE[$currencyCookie]
        : (string) ($currencyConfig['default'] ?? 'MAD');
    $rateMetadata = \App\Studio\CurrencyRates::displayMetadata($displayCurrency);
    $searchQuery = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
    $priceOnly = ($_GET['priced'] ?? null) === '1';
    $slugify = static fn (string $value): string => strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $value), '-'));
    $categoryMap = [];
    $categoryCounts = [];
    $engagementMap = [];

    foreach ($allServices as $service) {
        $categoryName = $service['category'];
        $categorySlug = $slugify($categoryName);
        $categoryMap[$categorySlug] = $categoryName;
        $categoryCounts[$categorySlug] = ($categoryCounts[$categorySlug] ?? 0) + 1;
        $engagementName = $service['engagement_type'] ?? 'Project';
        $engagementSlug = $slugify($engagementName);
        $engagementMap[$engagementSlug] = $engagementName;
    }
    asort($categoryMap, SORT_NATURAL | SORT_FLAG_CASE);
    asort($engagementMap, SORT_NATURAL | SORT_FLAG_CASE);

    $requestedCategory = is_string($_GET['category'] ?? null) ? $_GET['category'] : '';
    $selectedCategory = array_key_exists($requestedCategory, $categoryMap) ? $requestedCategory : '';
    $requestedEngagement = is_string($_GET['engagement'] ?? null) ? $_GET['engagement'] : '';
    $selectedEngagement = array_key_exists($requestedEngagement, $engagementMap) ? $requestedEngagement : '';
    $services = array_values(array_filter(
        $allServices,
        static function (array $service) use ($selectedCategory, $selectedEngagement, $searchQuery, $priceOnly, $slugify): bool {
            $categorySlug = $slugify($service['category']);
            $engagementSlug = $slugify($service['engagement_type'] ?? 'Project');

            if ($selectedCategory !== '' && $categorySlug !== $selectedCategory) {
                return false;
            }

            if ($selectedEngagement !== '' && $engagementSlug !== $selectedEngagement) {
                return false;
            }

            if ($priceOnly && empty($service['price_display'])) {
                return false;
            }

            if ($searchQuery !== '') {
                $searchable = implode(' ', [
                    $service['name'],
                    $service['category'],
                    $service['card_description'],
                    $service['format'],
                    $service['price_display'] ?? '',
                    $service['brand'] ?? '',
                ]);

                if (mb_stripos($searchable, $searchQuery) === false) {
                    return false;
                }
            }

            return true;
        },
    ));
    $servicesByCategory = [];
    foreach ($services as $service) {
        $servicesByCategory[$slugify($service['category'])][] = $service;
    }

@endphp

<x-studio::page
    title="Services — Numerimondes"
    description="Explore Numerimondes services, save a private shortlist and request a personal discussion with the founder and account manager."
>
    <style>
        .nm-services-marketplace .nm-page-heading {
            min-height: 53vh; padding: 140px 0 55px; display:flex; flex-direction:column; justify-content:center; gap:22px;
        }
        .nm-services-kicker { margin:0; color:#77746e; font:400 10px/1.35 var(--nm-title); letter-spacing:.15em; text-transform:uppercase; }
        .nm-services-marketplace .nm-page-heading h1 { margin:0; }
        .nm-services-intro { max-width:730px; margin:0; font:400 18px/1.4 var(--nm-body); }
        .nm-marketplace-layout { max-width:1600px; margin:0 auto; }
        .nm-marketplace-sidebar { position:sticky; top:100px; padding:24px 20px; border:1px solid var(--border-default, var(--nm-line)); border-radius:var(--radius-md, .375rem); background:var(--surface-panel, #fff); box-shadow:0 1px 2px rgba(15,23,42,.06), 0 8px 24px rgba(15,23,42,.04); }
        .nm-marketplace-sidebar h2 { margin:0 0 24px; font:400 11px/1.4 var(--nm-title); letter-spacing:.14em; text-transform:uppercase; }
        .nm-marketplace-search { display:grid; gap:10px; margin-bottom:25px; }
        .nm-marketplace-search label,.nm-marketplace-filter-title { font:400 9px/1.4 var(--nm-title); letter-spacing:.13em; text-transform:uppercase; }
        .nm-marketplace-search input[type=search] { width:100%; min-height:42px; padding:9px 10px; border:1px solid rgba(17,17,17,.24); border-radius:0; color:var(--nm-ink); background:transparent; font:400 14px/1.4 var(--nm-title); }
        .nm-marketplace-select { width:100%; min-height:42px; padding:8px 10px; border:1px solid rgba(17,17,17,.24); border-radius:0; color:var(--nm-ink); background:transparent; font:400 13px/1.4 var(--nm-title); }
        .nm-marketplace-filter { margin:0 0 22px; padding:0; border:0; }
        .nm-marketplace-filter-title { display:block; margin-bottom:12px; }
        .nm-marketplace-priced { display:flex; align-items:flex-start; gap:9px; font:400 14px/1.4 var(--nm-title); }
        .nm-marketplace-priced input { margin:3px 0 0; accent-color:#111; }
        .nm-marketplace-apply { width:100%; min-height:42px; border:0; border-bottom:1px solid currentColor; color:var(--nm-ink); background:none; font:400 9px/1.4 var(--nm-title); letter-spacing:.13em; text-align:left; text-transform:uppercase; cursor:pointer; }
        .nm-marketplace-reset { display:inline-block; margin-top:18px; color:var(--primary-600); font:400 12px/1.4 var(--nm-title); text-underline-offset:3px; }
        .nm-marketplace-main { min-width:0; }
        .nm-marketplace-commitments { display:grid; gap:10px; }
        .nm-marketplace-commitments h2 { margin:0 0 4px; font:400 10px/1.4 var(--nm-title); letter-spacing:.14em; text-transform:uppercase; }
        .nm-marketplace-commitment { min-height:150px; padding:18px 16px; border:1px solid var(--border-default, var(--nm-line)); border-radius:var(--radius-md, .375rem); background:var(--surface-panel, #fff); box-shadow:0 1px 2px rgba(15,23,42,.06), 0 8px 24px rgba(15,23,42,.04); }
        .nm-marketplace-commitment-number { margin:0 0 18px; color:#77746e; font:400 9px/1.3 var(--nm-title); letter-spacing:.12em; }
        .nm-marketplace-commitment h3 { margin:0 0 8px; font:400 11px/1.4 var(--nm-title); letter-spacing:.08em; text-transform:uppercase; }
        .nm-marketplace-commitment p { margin:0; font:400 13px/1.45 var(--nm-body); }
        .nm-marketplace-results { margin:0 0 15px; color:#77746e; font:400 10px/1.4 var(--nm-title); letter-spacing:.1em; text-transform:uppercase; }
        .nm-marketplace-grid { padding:0; }
        .nm-marketplace-category-group { margin:0 0 34px; }
        .nm-marketplace-category-heading { margin:0 0 14px; padding:0 0 9px; display:flex; align-items:baseline; justify-content:space-between; gap:16px; border-bottom:1px solid var(--border-default, var(--nm-line)); }
        .nm-marketplace-category-heading h2 { margin:0; font:400 11px/1.4 var(--nm-title); letter-spacing:.13em; text-transform:uppercase; }
        .nm-marketplace-category-heading span { color:#77746e; font:400 9px/1.4 var(--nm-title); letter-spacing:.08em; }
        .nm-marketplace-empty { padding:70px 30px; border-top:1px solid rgba(17,17,17,.2); text-align:center; }
        .nm-marketplace-empty h2 { margin:0 0 12px; font:400 13px/1.4 var(--nm-title); letter-spacing:.13em; text-transform:uppercase; }
        .nm-marketplace-empty p { margin:0 0 20px; font:400 16px/1.45 var(--nm-body); }
        .nm-marketplace-empty a { color:var(--primary-600); font:400 9px/1.4 var(--nm-title); letter-spacing:.14em; text-transform:uppercase; text-underline-offset:5px; }
        .nm-services-notes { margin-top:34px; padding-top:18px; border-top:1px solid var(--border-default, var(--nm-line)); }
        .nm-marketplace-pricing-note { margin:0; color:#77746e; font:400 12px/1.55 var(--nm-body); }
        .nm-marketplace-pricing-note a { color:var(--nm-ink); text-underline-offset:3px; }
        @media (max-width:900px) {
            .nm-marketplace-sidebar { padding:20px 15px; }
            .nm-marketplace-commitments { grid-column:1 / -1; grid-template-columns:repeat(2,minmax(0,1fr)); }
            .nm-marketplace-commitments h2 { grid-column:1 / -1; }
            .nm-marketplace-commitment { min-height:0; }
        }
        @media (max-width:700px) {
            .nm-services-marketplace .nm-page-heading { min-height:48vh; padding:120px 0 35px; gap:17px; }
            .nm-services-kicker { font-size:8px; }
            .nm-services-intro { font-size:15px; }
            .nm-marketplace-sidebar { position:static; width:100%; padding:18px; }
            .nm-marketplace-sidebar h2 { margin-bottom:18px; font-size:9px; }
            .nm-marketplace-main { width:100%; }
            .nm-marketplace-commitments { width:100%; grid-template-columns:1fr; }
            .nm-marketplace-commitments h2 { grid-column:auto; }
            .nm-marketplace-commitment { padding:18px; }
            .nm-marketplace-commitment-number { margin-bottom:12px; }
            .nm-marketplace-results { font-size:8px; }
            .nm-marketplace-empty { padding:50px 15px; }
            .nm-marketplace-empty h2 { font-size:11px; }
            .nm-marketplace-empty p { font-size:14px; }
        }
    </style>

    <main class="nm-page nm-services-marketplace">
        <header class="nm-page-heading">
            <p class="nm-services-kicker">Numerimondes marketplace · {{ count($allServices) }} offers</p>
            <h1>Find the right service for your next move.</h1>
            <p class="nm-services-intro">Explore the offers, save the ones you are considering, then share one considered brief. Yassine, our founder, and the account manager will follow up personally.</p>
        </header>

        <div class="nm-marketplace-layout">

            <section class="nm-marketplace-main" aria-label="Service offers">


              <x-webkernel-platform::section style="margin-bottom:2rem;">
                  Yo
              </x-webkernel-platform::section>

                <p class="nm-marketplace-results" role="status">{{ count($services) }} {{ count($services) === 1 ? 'offer' : 'offers' }}{{ $selectedCategory !== '' ? ' · '.$categoryMap[$selectedCategory] : '' }}{{ $selectedEngagement !== '' ? ' · '.$engagementMap[$selectedEngagement] : '' }}{{ $searchQuery !== '' ? ' · matching “'.$searchQuery.'”' : '' }}</p>

                @if ($services !== [])
                    @foreach ($categoryMap as $categorySlug => $categoryName)
                        @if (! empty($servicesByCategory[$categorySlug]))
                            <section class="nm-marketplace-category-group" aria-labelledby="nm-marketplace-category-{{ $categorySlug }}">
                                <header class="nm-marketplace-category-heading">
                                    <h2 id="nm-marketplace-category-{{ $categorySlug }}">{{ $categoryName }}</h2>
                                    <span>{{ count($servicesByCategory[$categorySlug]) }} {{ count($servicesByCategory[$categorySlug]) === 1 ? 'offer' : 'offers' }}</span>
                                </header>
                                <x-studio::marketplace-list
                                    :services="$servicesByCategory[$categorySlug]"
                                    :id="'marketplace-'.$categorySlug"
                                    :label="'Services in '.$categoryName"
                                />
                            </section>
                        @endif
                    @endforeach
                @else
                    <div class="nm-marketplace-empty">
                        <h2>No offer matches this search.</h2>
                        <p>Try a broader term or describe the outcome you need.</p>
                        <a href="{{ simple_site('studio', 'contact') }}">Discuss a tailored brief <x-webkernel::icon icon="arrow-up-right" set="lucide" width="14" height="14" class="nm-icon-arrow" aria-hidden="true" /></a>
                    </div>
                @endif
                <section class="nm-services-notes" aria-label="Service notes">
                    <p class="nm-marketplace-pricing-note">
                        @if ($displayCurrency === 'MAD')
                            MAD is the catalogue reference currency. Starting prices and scope are confirmed in a written proposal.
                        @else
                            {{ $displayCurrency }} prices are indicative conversions, buffered and rounded for readability. Reference rate date: {{ $rateMetadata['rate_date'] !== '' ? $rateMetadata['rate_date'] : 'not available' }}@if ($rateMetadata['checked_at'] !== '') (checked {{ $rateMetadata['checked_at'] }} UTC)@endif. Final currency and fee are confirmed in a written proposal.
                        @endif
                        <a href="{{ simple_site('studio', 'pricing-policy') }}">Pricing &amp; engagement policy</a>.
                    </p>
                </section>
            </section>

        </div>
    </main>
</x-studio::page>
