@php
    $pageData = \App\Studio\Presentation\ServicesPageData::build($_GET, $_COOKIE);
    $allServices = $pageData['allServices'];
    $mandateCount = $pageData['mandateCount'];
    $services = $pageData['services'];
    $servicesByPractice = $pageData['servicesByPractice'];
    $catalogData = $pageData['catalogData'];
    $revenueForce = $pageData['revenueForce'];
    $industryOptions = $pageData['industryOptions'];
    $businessNeedOptions = $pageData['businessNeedOptions'];
    $currencyOptions = $pageData['currencyOptions'];
    $displayCurrency = $pageData['displayCurrency'];
    $rateMetadata = $pageData['rateMetadata'];
    $searchQuery = $pageData['searchQuery'];
    $ownerOnly = $pageData['ownerOnly'];
    $practiceMap = $pageData['practiceMap'];
    $engagementMap = $pageData['engagementMap'];
    $selectedPractice = $pageData['selectedPractice'];
    $selectedEngagement = $pageData['selectedEngagement'];
    $threeColumnGrid = $pageData['threeColumnGrid'];
    $fourColumnGrid = $pageData['fourColumnGrid'];
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
        .nm-marketplace-practice-group { margin:0 0 34px; }
        .nm-marketplace-practice-heading { margin:0 0 14px; padding:0 0 9px; display:flex; align-items:baseline; justify-content:space-between; gap:16px; border-bottom:1px solid var(--border-default, var(--nm-line)); }
        .nm-marketplace-practice-heading h2 { margin:0; font:400 11px/1.4 var(--nm-title); letter-spacing:.13em; text-transform:uppercase; }
        .nm-marketplace-practice-heading span { color:#77746e; font:400 9px/1.4 var(--nm-title); letter-spacing:.08em; }
        .nm-marketplace-empty { padding:70px 30px; border-top:1px solid rgba(17,17,17,.2); text-align:center; }
        .nm-marketplace-empty h2 { margin:0 0 12px; font:400 13px/1.4 var(--nm-title); letter-spacing:.13em; text-transform:uppercase; }
        .nm-marketplace-empty p { margin:0 0 20px; font:400 16px/1.45 var(--nm-body); }
        .nm-marketplace-empty a { width:fit-content; display:inline-flex; align-items:center; gap:5px; color:var(--primary-600); font:400 9px/1.4 var(--nm-title); letter-spacing:.14em; text-transform:uppercase; text-underline-offset:5px; }
        .nm-services-marketplace [hidden] { display:none !important; }
        .nm-services-notes { margin-top:34px; padding-top:18px; border-top:1px solid var(--border-default, var(--nm-line)); }
        .nm-welcome-dialog { width:min(92vw,640px); max-height:88vh; padding:0; border:1px solid var(--border-default,#cbd5e1); border-radius:var(--radius-md,.375rem); color:var(--nm-ink); background:var(--surface-panel,#fff); box-shadow:0 24px 80px rgba(15,23,42,.24); }
        .nm-welcome-dialog::backdrop { background:rgba(15,23,42,.48); }
        .nm-welcome-content { padding:clamp(22px,5vw,40px); }
        .nm-welcome-content h2 { margin:0 0 10px; font:400 clamp(25px,4vw,36px)/1.12 var(--nm-title); }
        .nm-welcome-content p { margin:0 0 20px; }
        .nm-welcome-intents { display:grid; gap:9px; margin:0 0 20px; }
        .nm-welcome-intents button { min-height:48px; padding:12px 14px; border:1px solid var(--border-default,#cbd5e1); border-radius:var(--radius-md,.375rem); color:inherit; background:#fff; text-align:left; cursor:pointer; }
        .nm-welcome-intents button:focus-visible,.nm-welcome-content select:focus-visible { outline:3px solid #2563eb; outline-offset:2px; }
        .nm-welcome-actions { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; }
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
        @include('studio::partials.offerings.catalogue.introduction', ['mandate_count' => $mandateCount])

        @include('studio::partials.offerings.catalogue.three-engines', ['grid' => $threeColumnGrid])

        @include('studio::partials.offerings.catalogue.selector', ['industries' => $industryOptions, 'business_needs' => $businessNeedOptions])

        <div class="nm-marketplace-layout">

            <section class="nm-marketplace-main" aria-label="Service offers">
                @include('studio::partials.offerings.catalogue.filters', ['practiceMap' => $practiceMap, 'engagementMap' => $engagementMap, 'searchQuery' => $searchQuery, 'selectedPractice' => $selectedPractice, 'selectedEngagement' => $selectedEngagement, 'ownerOnly' => $ownerOnly])

                <p class="nm-marketplace-results" id="services-results" role="status" aria-live="polite"></p>

                @include('studio::partials.offerings.catalogue.catalogue-grid', ['services' => $services, 'practiceMap' => $practiceMap, 'servicesByPractice' => $servicesByPractice])

                @include('studio::partials.offerings.catalogue.offerings-details', ['grid' => $threeColumnGrid, 'revenueForce' => $revenueForce, 'modules' => $catalogData['modules'], 'playbooks' => $catalogData['playbooks']])

                @include('studio::partials.offerings.catalogue.commitments', ['grid' => $fourColumnGrid])

                <div class="nm-marketplace-empty" id="services-empty" hidden>
                    <h2>No offer matches this search.</h2>
                    <p>Try a broader term or describe the outcome you need.</p>
                    <a href="{{ simple_site('studio', 'contact') }}">Discuss a tailored brief <x-webkernel::icon icon="arrow-up-right" set="lucide" width="14" height="14" class="nm-icon-arrow" aria-hidden="true" /></a>
                </div>
                @include('studio::partials.offerings.catalogue.pricing-policy', ['currency' => $displayCurrency, 'rateMetadata' => $rateMetadata])
            </section>

        </div>
        <dialog class="nm-welcome-dialog" id="nm-welcome-dialog" aria-labelledby="nm-welcome-title" aria-describedby="nm-welcome-description">
            <div class="nm-welcome-content">
                <p class="nm-services-kicker">Numerimondes · commissioned work</p>
                <h2 id="nm-welcome-title">A considered starting point.</h2>
                <p id="nm-welcome-description">Choose a direction and display currency. You can explore the full catalogue at any time.</p>
                <h3>What would you like to shape?</h3>
                <div class="nm-welcome-intents">
                    <button type="button" data-intent="revenue">Grow sales and revenue</button>
                    <button type="button" data-intent="digital">Build a digital asset or platform</button>
                    <button type="button" data-intent="brand">Structure brand and operations</button>
                    <button type="button" data-intent="talent">Hire talent</button>
                </div>
                <div class="nm-welcome-actions">
                    <label for="nm-welcome-currency">Display currency
                        <select id="nm-welcome-currency" name="welcome_currency">
                            @foreach ($currencyOptions as $currencyCode => $currencyOption)
                                <option value="{{ $currencyCode }}" {{ $currencyCode === $displayCurrency ? 'selected' : '' }}>{{ $currencyCode }} · {{ $currencyOption['name'] ?? $currencyCode }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button type="button" class="nm-welcome-continue" data-intent="catalogue">Explore the catalogue</button>
                </div>
            </div>
        </dialog>
    </main>
    <script>
        (() => {
            const form = document.getElementById('services-filters');
            if (!form) return;

            const query = form.querySelector('[name="q"]');
            const practice = form.querySelector('[name="practice"]');
            const engagement = form.querySelector('[name="engagement"]');
            const owner = form.querySelector('[name="owner"]');
            const fitForm = document.getElementById('nm-service-fit-selector');
            const fitResult = document.getElementById('nm-service-fit-result');
            const fitSummary = document.getElementById('nm-service-fit-summary');
            const fitRecommendations = document.getElementById('nm-service-fit-recommendations');
            const cards = [...document.querySelectorAll('.nm-services-marketplace .nm-marketplace-card')];
            const groups = [...document.querySelectorAll('.nm-services-marketplace .nm-marketplace-practice-group')];
            const result = document.getElementById('services-results');
            const empty = document.getElementById('services-empty');

            const welcome = document.getElementById('nm-welcome-dialog');
            const welcomeCurrency = document.getElementById('nm-welcome-currency');
            const currencyCookie = @js((string) config('nm-studio.currencies.cookie', 'numerimondes_currency'));
            const allowedCurrencies = @js(array_keys($currencyOptions));
            const hasVisited = () => {
                try { if (localStorage.getItem('nm_visited') === '1') return true; } catch {}
                return document.cookie.split(';').some((entry) => entry.trim() === 'nm_visited=1');
            };
            const rememberVisit = () => {
                try { localStorage.setItem('nm_visited', '1'); } catch {}
                document.cookie = `nm_visited=1; Max-Age=31536000; Path=/; SameSite=Lax${location.protocol === 'https:' ? '; Secure' : ''}`;
            };
            const regionCurrency = { MA:'MAD', FR:'EUR', DE:'EUR', ES:'EUR', IT:'EUR', BE:'EUR', NL:'EUR', PT:'EUR', US:'USD', GB:'GBP', AE:'AED', SA:'SAR', CA:'CAD', CH:'CHF' };
            if (welcome && !hasVisited()) {
                let region = '';
                try { region = new Intl.Locale(navigator.language || 'en').region || ''; } catch {}
                const preferred = regionCurrency[region] || 'MAD';
                if (allowedCurrencies.includes(preferred)) welcomeCurrency.value = preferred;
                welcome.showModal();
            }
            const recordEvent = (event) => {
                const eventData = fitForm ? new FormData(fitForm) : new FormData();
                eventData.set('event', event);
                fetch('/services/recommendations', { method:'POST', credentials:'same-origin', keepalive:true, headers:{Accept:'application/json'}, body:eventData }).catch(() => {});
            };
            document.addEventListener('click', (event) => {
                if (event.target instanceof Element && event.target.closest('[name="currency"]')) recordEvent('currency_change');
            }, {capture:true});
            const finishWelcome = (intent) => {
                if (!welcome) return;
                const current = @js($displayCurrency);
                const chosen = allowedCurrencies.includes(welcomeCurrency.value) ? welcomeCurrency.value : current;
                document.cookie = `${currencyCookie}=${encodeURIComponent(chosen)}; Max-Age=31536000; Path=/; SameSite=Lax${location.protocol === 'https:' ? '; Secure' : ''}`;
                rememberVisit();
                recordEvent('welcome_complete');
                if (chosen !== current) recordEvent('currency_change');
                welcome.close();
                if (chosen !== current) { location.reload(); return; }
                if (intent === 'revenue') document.getElementById('revenue-force')?.scrollIntoView({behavior:'smooth',block:'start'});
                if (intent === 'catalogue') document.getElementById('services-filters')?.scrollIntoView({behavior:'smooth',block:'start'});
                if (intent === 'digital' || intent === 'brand' || intent === 'talent') {
                    practice.value = ({digital:'digital',brand:'brand',talent:'talent'})[intent];
                    practice.dispatchEvent(new Event('change', {bubbles:true}));
                    document.querySelector('.nm-marketplace-practice-group:not([hidden])')?.scrollIntoView({behavior:'smooth',block:'start'});
                }
            };
            welcome?.querySelectorAll('[data-intent]').forEach((button) => button.addEventListener('click', () => finishWelcome(button.dataset.intent)));
            welcome?.addEventListener('cancel', () => { rememberVisit(); recordEvent('welcome_complete'); });

            const update = () => {
                const term = query.value.trim().toLocaleLowerCase();
                let visibleCount = 0;

                cards.forEach((card) => {
                    const group = card.closest('.nm-marketplace-practice-group');
                    const matches = (!practice.value || group?.dataset.practice === practice.value)
                        && (!engagement.value || card.dataset.engagement === engagement.value)
                        && (!owner.checked || (Number(card.dataset.price) > 0 && Number(card.dataset.price) < 50000))
                        && (!term || card.dataset.search.includes(term));
                    card.hidden = !matches;
                    if (matches) visibleCount++;
                });

                groups.forEach((group) => {
                    const visible = [...group.querySelectorAll('.nm-marketplace-card')].filter((card) => !card.hidden).length;
                    group.hidden = visible === 0;
                    const count = group.querySelector('[data-practice-count]');
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
                const industry = fitForm.querySelector('[name="industry"]:checked');
                const businessNeed = fitForm.querySelector('[name="business_need"]:checked');
                const journey = Object.fromEntries([...fitForm.querySelectorAll('select[name]')]
                    .map((select) => [select.name, select.value]).filter(([, value]) => value !== ''));
                if (!industry && !businessNeed && Object.keys(journey).length === 0) return;
                const industryLabel = industry?.closest('label')?.querySelector('span')?.textContent.trim();
                const needLabel = businessNeed?.closest('label')?.querySelector('span')?.textContent.trim();
                fitSummary.textContent = [industryLabel && `You selected ${industryLabel} as your industry`, needLabel && `your business need is ${needLabel}`, Object.keys(journey).length && 'Your optional business profile refines the ranking'].filter(Boolean).join(' and ')+'.';
                try {
                    const response = await fetch(fitForm.action, {
                        method: 'POST',
                        body: new FormData(fitForm),
                        headers: { Accept: 'application/json' },
                        credentials: 'same-origin',
                    });
                    if (!response.ok) throw new Error('Recommendation request failed.');
                    const payload = await response.json();
                    fitRecommendations.replaceChildren();
                    (payload.recommendations || []).forEach((recommendation) => {
                        const item = document.createElement('li');
                        const link = document.createElement('a');
                        link.href = recommendation.href;
                        const language = (document.documentElement.lang || 'en').split('-')[0];
                        const names = recommendation.name || {};
                        link.textContent = names[language] || names.en || Object.values(names)[0] || recommendation.id;
                        item.append(link);
                        if (recommendation.type) {
                            const type = document.createElement('span');
                            type.textContent = ` · ${recommendation.type}`;
                            item.append(type);
                        }
                        fitRecommendations.append(item);
                    });
                    if (!fitRecommendations.childElementCount) {
                        const item = document.createElement('li');
                        item.textContent = 'Explore the full catalogue below for a tailored fit.';
                        fitRecommendations.append(item);
                    }
                } catch {
                    fitRecommendations.replaceChildren();
                    const item = document.createElement('li');
                    item.textContent = 'Explore the full catalogue below for a tailored fit.';
                    fitRecommendations.append(item);
                }
                fitResult.hidden = false;
                fitForm.querySelector('[data-reset-fit]').hidden = false;
                update();

                document.getElementById('services-results')?.scrollIntoView({
                    behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
                    block: 'start',
                });
            });
            fitForm?.addEventListener('reset', () => window.setTimeout(() => {
                fitResult.hidden = true;
                fitForm.querySelector('[data-reset-fit]').hidden = true;
                update();
            }, 0));
            fitForm?.querySelector('[data-reset-fit]')?.addEventListener('click', () => fitForm.reset());
            update();
        })();
    </script>
</x-studio::page>
