@php
    $catalogData = \App\Studio\Catalog\MandateCatalog::data();
    $industryOptions = \App\Studio\Catalog\Taxonomy\Industry::options();
    $businessNeedOptions = \App\Studio\Catalog\Taxonomy\BusinessNeed::options();
    $legacyServices = \App\Studio\Catalog\ServiceCatalog::all();
    $imageReferences = [
        'brand' => 'brand-kit-complete',
        'digital' => 'website-build',
        'systems' => 'crm-client-portal',
        'intel' => 'ops-audit',
        'recruit' => 'remote-talent-search',
        'sales' => 'revenue-desk-pilot',
    ];
    $allServices = [];
    foreach ($catalogData['mandates'] as $mandate) {
        $reference = $legacyServices[$imageReferences[$mandate['cat']]] ?? [];
        $mandate['slug'] = $mandate['id'];
        $mandate['engagement_type'] = match ($mandate['fmt']) {
            'oneoff' => 'One-time',
            'recruit' => 'Recruitment mandate',
            default => 'Project',
        };
        $mandate['format'] = $mandate['tier'];
        $mandate['card_description'] = $mandate['scope'];
        $mandate['price_display'] = $mandate['price_text']
            ?? ($mandate['price_mad'] === null ? '' : 'From '.price((float) $mandate['price_mad'], 'MAD'));
        $mandate['price_lead'] = 'From ';
        $mandate['price_amount'] = $mandate['price_mad'];
        $mandate['price_currency'] = 'MAD';
        $mandate['status'] = $mandate['terms'];
        $mandate['image_source'] = $reference['image_source'] ?? '';
        $mandate['image'] = $reference['image'] ?? '';
        $mandate['image_alt'] = $reference['image_alt'] ?? $mandate['name'];
        $mandate['image_width'] = $reference['image_width'] ?? 1200;
        $mandate['image_height'] = $reference['image_height'] ?? 800;
        $mandate['href'] = simple_site('studio', 'services/'.$mandate['id']);
        $mandate['class'] = 'marketplace-service--'.$mandate['id'];
        $mandate['catalog_mandate'] = true;
        $allServices[$mandate['slug']] = $mandate;
    }
    $currencyConfig = config('nm-studio.currencies', []);
    $currencyOptions = is_array($currencyConfig['options'] ?? null) ? $currencyConfig['options'] : [];
    $currencyCookie = (string) ($currencyConfig['cookie'] ?? 'numerimondes_currency');
    $displayCurrency = is_string($_COOKIE[$currencyCookie] ?? null) && isset($currencyOptions[$_COOKIE[$currencyCookie]])
        ? $_COOKIE[$currencyCookie]
        : (string) ($currencyConfig['default'] ?? 'MAD');
    $rateMetadata = \App\Studio\Pricing\CurrencyRates::displayMetadata($displayCurrency);
    $searchQuery = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
    $ownerOnly = ($_GET['owner'] ?? null) === '1';
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
    $services = $allServices;
    $servicesByCategory = [];
    foreach ($services as $service) {
        $servicesByCategory[$slugify($service['category'])][] = $service;
    }
    $threeColumnGrid = (new \Webkernel\Platform\Support\View\ComponentAttributeBag())->grid([
        'default' => 1,
        'md' => 2,
        'lg' => 3,
    ])->style('gap: 1rem');
    $fourColumnGrid = (new \Webkernel\Platform\Support\View\ComponentAttributeBag())->grid([
        'default' => 1,
        'md' => 2,
        'lg' => 4,
    ])->style('gap: 1rem');

@endphp

<x-studio::page
    title="Services — Numerimondes"
    description="Digital assets, systems and teams, built to compound. Fixed-scope, partner-led mandates from Numerimondes."
>
    <style>
        .nm-services-marketplace .nm-page-heading {
            min-height: 38vh; padding: 110px 0 40px; display:flex; flex-direction:column; justify-content:center; gap:22px;
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
        .nm-marketplace-empty a { width:fit-content; display:inline-flex; align-items:center; gap:5px; color:var(--primary-600); font:400 9px/1.4 var(--nm-title); letter-spacing:.14em; text-transform:uppercase; text-underline-offset:5px; }
        .nm-services-marketplace [hidden] { display:none !important; }
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
            .nm-services-marketplace .nm-page-heading { min-height:36vh; padding:95px 0 30px; gap:17px; }
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
            <p class="nm-services-kicker">Numerimondes Practice · Commissioned work · {{ count($allServices) }} mandates</p>
            <h1>Digital assets, systems and teams, built to compound.</h1>
            <p class="nm-services-intro">
            We design and deliver the brand, digital and operating assets that established and growing companies rely on. Fixed scope, fixed fee, partner-led from first brief to handover.
              </p>
        </header>

        <div style="margin-bottom:2rem">
            <div {{ $threeColumnGrid }}>
                <x-webkernel-platform::card compact>
                    <x-webkernel-platform::badge color="gray" size="sm">01 · Flagship assets</x-webkernel-platform::badge>
                    <h3>For established groups and institutions</h3>
                    <p>Search-engineered platforms, proprietary software and institutional brand doctrine. Mandates from 150,000 MAD.</p>
                </x-webkernel-platform::card>
                <x-webkernel-platform::card compact>
                    <x-webkernel-platform::badge color="gray" size="sm">02 · Revenue force</x-webkernel-platform::badge>
                    <h3>For owner-led businesses that need to sell</h3>
                    <p>We recruit the salesperson, install the CRM and supervise the rhythm. The owner keeps control; we supply the method.</p>
                </x-webkernel-platform::card>
                <x-webkernel-platform::card compact>
                    <x-webkernel-platform::badge color="gray" size="sm">03 · Vertical software</x-webkernel-platform::badge>
                    <h3>For sectors still run on paper and spreadsheets</h3>
                    <p>One software engine deployed sector by sector: driving schools, private schools, bookshops. Monthly subscription.</p>
                </x-webkernel-platform::card>
            </div>
        </div>

        @include('studio::service-fit-selector', ['industries' => $industryOptions, 'business_needs' => $businessNeedOptions])

        <div class="nm-marketplace-layout">

            <section class="nm-marketplace-main" aria-label="Service offers">
               <style>
                    .nm-services-filter-section { margin-bottom:2rem; }
                    .nm-services-filter-grid { gap:1rem; }
                </style>
                <x-webkernel-platform::section class="nm-services-filter-section">
                    <form id="services-filters" aria-label="Filter services">
                        @php
                            $filterGrid = (new \Webkernel\Platform\Support\View\ComponentAttributeBag())->grid([
                                'default' => 1,
                                'md' => 2,
                                'lg' => 5,
                            ])->class('items-end nm-services-filter-grid');
                            $searchInputColumn = (new \Webkernel\Platform\Support\View\ComponentAttributeBag())->gridColumn([
                                'md' => 2,
                                'lg' => 2,
                            ])->class('min-w-0');
                        @endphp

                        <div {{ $filterGrid }}>
                            <div {{ $searchInputColumn }}>
                                <x-webkernel-platform::input.wrapper>
                                    <x-webkernel-platform::input
                                        id="services-query"
                                        name="q"
                                        type="search"
                                        placeholder="Search offers"
                                        :value="$searchQuery"
                                    ></x-webkernel-platform::input>
                                </x-webkernel-platform::input.wrapper>
                            </div>

                                <div>
                                    <x-webkernel-platform::input.wrapper>
                                        <x-webkernel-platform::input.select id="services-category" name="category">
                                            <option value="">All categories</option>
                                            @foreach ($categoryMap as $categorySlug => $categoryName)
                                                <option value="{{ $categorySlug }}" @selected($selectedCategory === $categorySlug)>{{ $categoryName }}</option>
                                            @endforeach
                                        </x-webkernel-platform::input.select>
                                    </x-webkernel-platform::input.wrapper>
                                </div>

                                <div>
                                    <x-webkernel-platform::input.wrapper>
                                        <x-webkernel-platform::input.select id="services-engagement" name="engagement">
                                            <option value="">All formats</option>
                                            @foreach ($engagementMap as $engagementSlug => $engagementName)
                                                <option value="{{ $engagementSlug }}" @selected($selectedEngagement === $engagementSlug)>{{ $engagementName }}</option>
                                            @endforeach
                                        </x-webkernel-platform::input.select>
                                    </x-webkernel-platform::input.wrapper>
                                </div>

                                <div>
                                    <label class="nm-marketplace-priced" for="services-owner">
                                        <input id="services-owner" name="owner" type="checkbox" value="1" {{ $ownerOnly ? 'checked' : '' }}>
                                        <span>Owner-managed budgets (under 50,000 MAD)</span>
                                    </label>
                                </div>

                        </div>

                        <div class="mt-4">
                            <x-webkernel-platform::button.group>
                                <x-webkernel-platform::button
                                    type="reset"
                                    color="gray"
                                    outlined
                                    size="sm"
                                    icon="heroicon-o-arrow-path"
                                    aria-label="Clear all filters"
                                    tooltip="Clear all filters"
                                ></x-webkernel-platform::button>
                            </x-webkernel-platform::button.group>
                        </div>

                    </form>
                </x-webkernel-platform::section>

                <p class="nm-marketplace-results" id="services-results" role="status" aria-live="polite"></p>

                @if ($services !== [])
                    @foreach ($categoryMap as $categorySlug => $categoryName)
                        @if (! empty($servicesByCategory[$categorySlug]))
                            @php
                                $categoryServices = $servicesByCategory[$categorySlug];
                                $marketplaceId = 'marketplace-'.$categorySlug;
                                $marketplaceLabel = 'Services in '.$categoryName;
                            @endphp
                            <section class="nm-marketplace-category-group" data-category="{{ $categorySlug }}" aria-labelledby="nm-marketplace-category-{{ $categorySlug }}">
                                <header class="nm-marketplace-category-heading">
                                    <h2 id="nm-marketplace-category-{{ $categorySlug }}">{{ $categoryName }}</h2>
                                    <span data-category-count>{{ count($servicesByCategory[$categorySlug]) }} {{ count($servicesByCategory[$categorySlug]) === 1 ? 'offer' : 'offers' }}</span>
                                </header>
                                @include('studio::marketplace-list', ['services' => $categoryServices, 'id' => $marketplaceId, 'label' => $marketplaceLabel])
                            </section>
                        @endif
                    @endforeach
                @endif

                <x-webkernel-platform::section heading="Revenue Force · Owner-led businesses">
                    <h2>Hire the salesperson. Install the system. Manage the rhythm.</h2>
                    <p>You carry the salary. We recruit, equip and supervise, so the sales function runs without becoming a second job for the owner.</p>
                    <div {{ $threeColumnGrid }}>
                        <x-webkernel-platform::card compact>
                            <x-webkernel-platform::badge color="gray" size="sm">Step 1 · Recruit</x-webkernel-platform::badge>
                            <h3>Salesperson selection</h3>
                            <p>Sourcing, live sales simulations and selection of a field or inside salesperson, through The Best Recruit.</p>
                            <p>46% of one month’s salary, on signature.</p>
                        </x-webkernel-platform::card>
                        <x-webkernel-platform::card compact>
                            <x-webkernel-platform::badge color="gray" size="sm">Step 2 · Equip</x-webkernel-platform::badge>
                            <h3>CRM, scripts and qualified files</h3>
                            <p>A configured CRM, qualified prospect files, the sales narrative and a tracking grid.</p>
                            <p>{{ price(7500, 'MAD') }} · one-time setup</p>
                        </x-webkernel-platform::card>
                        <x-webkernel-platform::card compact>
                            <x-webkernel-platform::badge color="gray" size="sm">Step 3 · Supervise</x-webkernel-platform::badge>
                            <h3>Weekly commercial direction</h3>
                            <p>A partner reviews calls and KPIs each week, coaches the salesperson and reports to the owner.</p>
                            <p>{{ price(4500, 'MAD') }}–{{ price(6500, 'MAD') }} per month</p>
                        </x-webkernel-platform::card>
                    </div>
                    <form class="nm-shortlist-button" action="/shortlist/toggle" method="post">
                        @csrf
                        <input type="hidden" name="service" value="sales-force">
                        <input type="hidden" name="action" value="{{ in_array('sales-force', \App\Studio\Shortlist\ServiceShortlist::all(), true) ? 'remove' : 'add' }}">
                        <input type="hidden" name="return_to" value="/services">
                        <x-webkernel-platform::button type="submit" color="primary" size="sm">{{ in_array('sales-force', \App\Studio\Shortlist\ServiceShortlist::all(), true) ? 'Remove from shortlist' : 'Add Revenue Force to shortlist' }}</x-webkernel-platform::button>
                    </form>
                </x-webkernel-platform::section>

                <x-webkernel-platform::section heading="Vertical Software · Early Access" style="margin-top:2rem">
                    <h2>One software engine, deployed sector by sector.</h2>
                    <p>Per-establishment monthly subscription, plus onboarding and data import. Join the waitlist: we prioritise the sectors that respond first.</p>
                    <div {{ $threeColumnGrid }}>
                        @foreach ($catalogData['modules'] as $module)
                            <x-webkernel-platform::card compact>
                                <x-webkernel-platform::badge color="success" size="sm">Waitlist open · Early access</x-webkernel-platform::badge>
                                <h3>{{ $module['name'] }}</h3>
                                <p>{{ $module['scope'] }}</p>
                                <p>Onboarding and data import: {{ price(1500, 'MAD') }}–{{ price(3000, 'MAD') }}</p>
                                <p><strong>From {{ price((float) $module['price_mad'], 'MAD') }} / month</strong></p>
                                <x-studio::shortlist-button :service="$module" :compact="true" />
                            </x-webkernel-platform::card>
                        @endforeach
                    </div>
                </x-webkernel-platform::section>

                <x-webkernel-platform::section heading="Capability Transfer · Executive Playbooks" style="margin-top:2rem">
                    <h2>Keep control of the method. Delegate execution when you choose.</h2>
                    <p>Each Playbook transfers a complete operating architecture (processes, decision grids, selection criteria, scripts and technical structure) with a working session with a partner. If you commission full execution within 30 days, the Playbook fee is credited in full.</p>
                    <div {{ $threeColumnGrid }}>
                        @foreach ($catalogData['playbooks'] as $playbook)
                            <x-webkernel-platform::card compact>
                                <x-webkernel-platform::badge color="warning" size="sm">Credited in full within 30 days</x-webkernel-platform::badge>
                                <h3>{{ $playbook['name'] }}</h3>
                                <p>{{ $playbook['target'] }}</p>
                                <p>{{ $playbook['deliverables'] }}</p>
                                <p>Leads to: {{ $playbook['gateway'] }}</p>
                                <p><strong>{{ price((float) $playbook['price_mad'], 'MAD') }}</strong></p>
                                <x-studio::shortlist-button :service="$playbook" :compact="true" />
                            </x-webkernel-platform::card>
                        @endforeach
                    </div>
                </x-webkernel-platform::section>

                <x-webkernel-platform::section heading="Our commitments" style="margin-top:2rem">
                    <div {{ $fourColumnGrid }}>
                        <x-webkernel-platform::card compact><x-webkernel-platform::badge color="gray" size="sm">01 · Clarity</x-webkernel-platform::badge><h3>Scope fixed upfront</h3><p>Objectives, responsibilities and terms agreed before any work starts.</p></x-webkernel-platform::card>
                        <x-webkernel-platform::card compact><x-webkernel-platform::badge color="gray" size="sm">02 · Team</x-webkernel-platform::badge><h3>A team built for the mandate</h3><p>Specialists are assembled for each engagement, not allocated from a bench.</p></x-webkernel-platform::card>
                        <x-webkernel-platform::card compact><x-webkernel-platform::badge color="gray" size="sm">03 · Accountability</x-webkernel-platform::badge><h3>One accountable lead</h3><p>Numerimondes orchestrates the whole and owns the quality of the result.</p></x-webkernel-platform::card>
                        <x-webkernel-platform::card compact><x-webkernel-platform::badge color="gray" size="sm">04 · Transparency</x-webkernel-platform::badge><h3>Clear milestones</h3><p>Timeline, deliverables and fees are detailed and accepted before commitment.</p></x-webkernel-platform::card>
                    </div>
                </x-webkernel-platform::section>

                <div class="nm-marketplace-empty" id="services-empty" hidden>
                    <h2>No offer matches this search.</h2>
                    <p>Try a broader term or describe the outcome you need.</p>
                    <a href="{{ simple_site('studio', 'contact') }}">Discuss a tailored brief <x-webkernel::icon icon="arrow-up-right" set="lucide" width="14" height="14" class="nm-icon-arrow" aria-hidden="true" /></a>
                </div>
                <section class="nm-services-notes" aria-label="Service notes">
                    <p class="nm-marketplace-pricing-note">
                        @if ($displayCurrency === 'MAD')
                        MAD is the catalogue reference currency. Starting prices reflect senior-led architecture and execution; final scope, timelines, and multi-mandate terms are confirmed in a written proposal prior to commencement.
                        @else
                            {{ $displayCurrency }} prices are indicative conversions, buffered and rounded for readability. Reference rate date: {{ $rateMetadata['rate_date'] !== '' ? $rateMetadata['rate_date'] : 'not available' }}@if ($rateMetadata['checked_at'] !== '') (checked {{ $rateMetadata['checked_at'] }} UTC)@endif. Final currency and fee are confirmed in a written proposal.
                        @endif
                        <a href="{{ simple_site('studio', 'pricing-policy') }}">Pricing &amp; engagement policy</a>.
                    </p>
                </section>
            </section>

        </div>
    </main>
    <script>
        (() => {
            const form = document.getElementById('services-filters');
            if (!form) return;

            const query = form.querySelector('[name="q"]');
            const category = form.querySelector('[name="category"]');
            const engagement = form.querySelector('[name="engagement"]');
            const owner = form.querySelector('[name="owner"]');
            const fitForm = document.getElementById('nm-service-fit-selector');
            const fitResult = document.getElementById('nm-service-fit-result');
            const fitSummary = document.getElementById('nm-service-fit-summary');
            let fitSelection = null;
            const cards = [...document.querySelectorAll('.nm-services-marketplace .nm-marketplace-card')];
            const groups = [...document.querySelectorAll('.nm-services-marketplace .nm-marketplace-category-group')];
            const result = document.getElementById('services-results');
            const empty = document.getElementById('services-empty');

            const update = () => {
                const term = query.value.trim().toLocaleLowerCase();
                let visibleCount = 0;

                cards.forEach((card) => {
                    const group = card.closest('.nm-marketplace-category-group');
                    const matches = (!category.value || group?.dataset.category === category.value)
                        && (!engagement.value || card.dataset.engagement === engagement.value)
                        && (!owner.checked || !Number(card.dataset.price) || Number(card.dataset.price) < 50000)
                        && (!fitSelection || (card.dataset.industries || '').split(',').includes(fitSelection.industry))
                        && (!fitSelection || (card.dataset.businessNeeds || '').split(',').includes(fitSelection.businessNeed))
                        && (!term || card.dataset.search.includes(term));
                    card.hidden = !matches;
                    if (matches) visibleCount++;
                });

                groups.forEach((group) => {
                    const visible = [...group.querySelectorAll('.nm-marketplace-card')].filter((card) => !card.hidden).length;
                    group.hidden = visible === 0;
                    const count = group.querySelector('[data-category-count]');
                    if (count) count.textContent = `${visible} ${visible === 1 ? 'offer' : 'offers'}`;
                });

                result.textContent = `${visibleCount} ${visibleCount === 1 ? 'offer' : 'offers'}`;
                empty.hidden = visibleCount !== 0;
            };

            form.addEventListener('input', update);
            form.addEventListener('change', update);
            form.addEventListener('reset', () => window.setTimeout(update, 0));
            form.addEventListener('submit', (event) => event.preventDefault());
            fitForm?.addEventListener('submit', async (event) => {
                event.preventDefault();
                if (!fitForm.reportValidity()) return;

                const industry = fitForm.querySelector('[name="industry"]:checked');
                const businessNeed = fitForm.querySelector('[name="business_need"]:checked');
                if (!industry || !businessNeed) return;

                fitSelection = {
                    industry: industry.value,
                    businessNeed: businessNeed.value,
                };
                const industryLabel = industry.closest('label')?.querySelector('span')?.textContent.trim() ?? industry.value;
                const needLabel = businessNeed.closest('label')?.querySelector('span')?.textContent.trim() ?? businessNeed.value;
                fitSummary.textContent = `You selected ${industryLabel} as your industry and ${needLabel} as your business need.`;
                fitResult.hidden = false;
                fitForm.querySelector('[data-reset-fit]').hidden = false;
                update();

                try {
                    await fetch(fitForm.action, {
                        method: 'POST',
                        body: new FormData(fitForm),
                        headers: { Accept: 'application/json' },
                        credentials: 'same-origin',
                    });
                } catch {}

                document.getElementById('services-results')?.scrollIntoView({
                    behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
                    block: 'start',
                });
            });
            fitForm?.addEventListener('reset', () => window.setTimeout(() => {
                fitSelection = null;
                fitResult.hidden = true;
                fitForm.querySelector('[data-reset-fit]').hidden = true;
                update();
            }, 0));
            fitForm?.querySelector('[data-reset-fit]')?.addEventListener('click', () => fitForm.reset());
            update();
        })();
    </script>
</x-studio::page>
