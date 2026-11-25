@php
    $serviceCatalog = \App\Studio\Catalog\OfferingCatalog::all();
    $selectedSlugs = \App\Studio\Http\ServiceInquiryController::selectedSlugs();
    $selectedServices = array_values(array_filter(
        array_map(static fn (string $slug): ?array => $serviceCatalog[$slug] ?? null, $selectedSlugs),
        'is_array',
    ));
    $prepareOfferCard = static function (array $offer): array {
        $id = (string) ($offer['id'] ?? '');
        $parent = \App\Studio\Catalog\OfferingCatalog::parentOf($id, 'extension');
        $offer['quantity_unit'] = null;
        foreach ($offer['prices'] ?? [] as $priceComponent) {
            if (is_array($priceComponent) && is_string($priceComponent['unit'] ?? null) && $priceComponent['unit'] !== '') {
                $offer['quantity_unit'] = $priceComponent['unit'];
                break;
            }
        }
        $offer['slug'] = $id;
        $offer['category'] = (string) ($offer['category'] ?? ucfirst((string) ($offer['type'] ?? 'Offering')));
        $offer['format'] = (string) ($offer['format'] ?? 'Offering');
        $offer['status'] = (string) ($offer['status'] ?? 'Taking enquiries');
        $offer['href'] = simple_site('studio', 'services/'.($parent['id'] ?? $id));
        if ($parent !== null) {
            $offer['parent'] = $parent['id'];
            $offer['parent_name'] = $parent['name'];
        }
        return $offer;
    };
    $selectedServices = array_map($prepareOfferCard, $selectedServices);
    $projectCatalog = \App\Studio\Catalog\ProjectCatalog::all();
    $selectedProjectSlugs = \App\Studio\Http\ServiceInquiryController::selectedProjects();
    $selectedProjects = array_values(array_filter(
        array_map(static fn (string $slug): ?array => $projectCatalog[$slug] ?? null, $selectedProjectSlugs),
        'is_array',
    ));
    $allServices = array_values(array_filter(
        $serviceCatalog,
        static fn (array $service): bool => ($service['visibility'] ?? 'catalog') === 'catalog',
    ));
    $recommendedSlugs = [];
    foreach ($selectedServices as $selectedService) {
        foreach (($selectedService['relations'] ?? []) as $relation) {
            $relatedId = is_array($relation) ? ($relation['offering_id'] ?? null) : null;
            if (is_string($relatedId) && ($relation['type'] ?? '') !== 'extension'
                && ($relation['visibility'] ?? 'detail_only') === 'catalog'
                && isset($serviceCatalog[$relatedId]) && ! in_array($relatedId, $selectedSlugs, true)) {
                $recommendedSlugs[] = $relatedId;
            }
        }
    }
    if ($recommendedSlugs === []) {
        $recommendedSlugs = array_column(array_slice($allServices, 0, 4), 'id');
    }
    $recommendedServices = array_values(array_filter(
        array_map(static fn (string $slug): ?array => $serviceCatalog[$slug] ?? null, array_unique($recommendedSlugs)),
        static fn (?array $offer): bool => is_array($offer) && ! in_array($offer['id'] ?? null, $selectedSlugs, true)
            && ($offer['active'] ?? false) === true && ($offer['visibility'] ?? 'catalog') === 'catalog',
    ));
    if ($recommendedServices === []) {
        $recommendedServices = array_values(array_filter(
            $allServices,
            static fn (array $offer): bool => ! in_array($offer['id'], $selectedSlugs, true),
        ));
    }
    $recommendedServices = array_slice($recommendedServices, 0, 3);
    $recommendedServices = array_map($prepareOfferCard, $recommendedServices);
    $service = $selectedServices[0] ?? null;
    $contextNames = array_map(
        static fn (string $name): string => preg_replace('/\s*·\s*/u', ' ', $name) ?? $name,
        [...array_map(static fn (array $offer): string => __t($offer['name'] ?? []), $selectedServices), ...array_column($selectedProjects, 'name')],
    );
    $contextLabel = match (count($contextNames)) {
        0 => '',
        1 => $contextNames[0],
        2 => $contextNames[0].' and '.$contextNames[1],
        default => implode(', ', array_slice($contextNames, 0, -1)).', and '.end($contextNames),
    };
    $studioConfig = config('nm-studio', []);
    $currencySettings = is_array($studioConfig['currencies'] ?? null) ? $studioConfig['currencies'] : [];
    $currencyCookie = is_string($currencySettings['cookie'] ?? null) ? $currencySettings['cookie'] : 'numerimondes_currency';
    $displayCurrency = strtoupper((string) ($_COOKIE[$currencyCookie] ?? $currencySettings['default'] ?? 'MAD'));
    if (!isset($currencySettings['options'][$displayCurrency])) $displayCurrency = 'MAD';
    $inquiryError = \Webkernel\Session\Session::get('numerimondes.service_inquiry.error');
    $inquiryErrorMessage = match ($inquiryError) {
        'invalid' => 'Review the required fields and consent, then submit again.',
        'pricing' => 'The current quote could not be prepared. Please try again in a moment.',
        'delivery' => 'Your request could not be delivered. Please try again or use WhatsApp.',
        default => '',
    };
    $recruitmentSelected = count(array_filter($selectedServices, static fn (array $offer): bool => is_numeric($offer['prices'][0]['percentage'] ?? null))) > 0;
    $crmConfig = is_array($studioConfig['crm'] ?? null) ? $studioConfig['crm'] : [];
    $accountStatus = \App\Studio\Integration\Perfex\PerfexAccountSession::current();
    $isClientConnected = $accountStatus['client_id'] > 0;
    $isStaffConnected = $accountStatus['staff_id'] > 0;
    $whatsappNumber = preg_replace('/\D+/', '', (string) ($studioConfig['contact']['phone']['display'] ?? ''));
    $whatsappMessage = match (count($contextNames)) {
        0 => "Hi Numerimondes, I have a project I'd like to discuss. Could we talk?",
        1 => "Hi Numerimondes, I'm interested in {$contextLabel}. Could we talk about whether it's a good fit?",
        default => "Hi Numerimondes, I'm considering {$contextLabel}. Which one would you recommend starting with for my goals?",
    };
    $whatsappHref = 'https://wa.me/'.$whatsappNumber.'?text='.rawurlencode($whatsappMessage);
@endphp

<x-studio::page
    title="A private discussion — Numerimondes"
    description="Share the services you are considering and request a personal follow-up from Numerimondes."
    robots="noindex,follow"
    rss="false"
>
    <style>
        .nm-service-inquiry { width:100%; max-width:1040px; margin:0 auto; padding:180px 0 120px; }
        .nm-service-inquiry h1 { margin:0; font:400 16px/1.5 var(--nm-title); letter-spacing:.18em; text-transform:uppercase; }
        .nm-service-inquiry-lead { max-width:650px; margin:28px 0 50px; font:400 18px/1.4 var(--nm-body); }
        .nm-service-inquiry-offer { margin:0 0 38px; color:#77746e; font:400 10px/1.5 var(--nm-title); letter-spacing:.14em; text-transform:uppercase; }
        .nm-service-inquiry-selection,.nm-service-inquiry-recommendations { margin:0 0 55px; }
        .nm-service-inquiry-selection h2,.nm-service-inquiry-recommendations h2 { margin:0 0 12px; font:400 12px/1.4 var(--nm-title); letter-spacing:.14em; text-transform:uppercase; }
        .nm-service-inquiry-selection[hidden] { display:none; }
        .nm-service-inquiry-selection > p,.nm-service-inquiry-recommendations > p { max-width:720px; margin:0 0 22px; font:400 15px/1.45 var(--nm-body); }
        .nm-inquiry-offer-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(min(100%,220px),1fr)); gap:10px; }
        .nm-service-inquiry-projects { margin:0 0 55px; padding:18px 20px; border:1px solid var(--border-default,var(--nm-line)); border-radius:var(--radius-md,.375rem); background:var(--surface-panel,#fff); }
        .nm-service-inquiry-projects h2 { margin:0 0 12px; font:400 10px/1.4 var(--nm-title); letter-spacing:.13em; text-transform:uppercase; }
        .nm-service-inquiry-projects ul { display:grid; gap:8px; margin:0; padding:0; list-style:none; }
        .nm-service-inquiry-projects li { display:flex; align-items:center; justify-content:space-between; gap:20px; font:400 13px/1.4 var(--nm-body); }
        .nm-service-inquiry-projects form { margin:0; }
        .nm-service-inquiry-projects button { padding:3px 0; border:0; color:var(--primary-600); background:transparent; font:400 9px/1.4 var(--nm-title); letter-spacing:.12em; text-transform:uppercase; cursor:pointer; }
        .nm-service-inquiry-contact { margin:70px 0 0; padding:28px 0 0; border-top:1px solid var(--nm-line); }
        .nm-service-inquiry-contact h2 { margin:0; font:400 12px/1.4 var(--nm-title); letter-spacing:.14em; text-transform:uppercase; }
        .nm-service-inquiry-contact p { max-width:620px; margin:14px 0 24px; font:400 15px/1.45 var(--nm-body); }
        .nm-service-inquiry-contact-actions { display:flex; flex-wrap:wrap; align-items:center; gap:22px; }
        .nm-service-inquiry-contact-actions a { color:var(--nm-ink); font:400 10px/1.4 var(--nm-title); letter-spacing:.13em; text-decoration:none; text-transform:uppercase; }
        .nm-service-inquiry-contact-actions a:first-child { padding:12px 16px; border:1px solid var(--nm-ink); }
        .nm-service-inquiry-contact-actions a:hover { opacity:.62; }
        .nm-service-request-form { display:grid; gap:18px; margin-top:36px; }
        .nm-service-request-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,240px),1fr)); gap:16px; }
        .nm-service-request-field { display:grid; gap:6px; }
        .nm-service-request-field input,.nm-service-request-field select,.nm-service-request-field textarea { width:100%; min-height:44px; padding:10px 12px; border:1px solid var(--border-default,#cbd5e1); border-radius:var(--radius-md,.375rem); background:var(--surface-panel,#fff); color:var(--text-primary,var(--nm-ink)); font:400 14px/1.4 var(--nm-body); }
        .nm-service-request-field textarea { min-height:120px; resize:vertical; }
        .nm-service-request-error { padding:12px 14px; border:1px solid #b91c1c; color:#991b1b; background:#fef2f2; }
        .nm-service-request-trap { position:absolute; width:1px; height:1px; overflow:hidden; clip-path:inset(50%); }
        @media (max-width:700px) {
            .nm-service-inquiry { padding:130px 0 75px; }
            .nm-service-inquiry { padding-top:120px; padding-bottom:75px; }
            .nm-service-inquiry h1 { font-size:12px; }
            .nm-service-inquiry-lead { margin:20px 0 38px; font-size:15px; }
            .nm-service-inquiry-offer,.nm-service-inquiry label { font-size:8px; }
            .nm-service-inquiry-contact { margin-top:50px; }
            .nm-service-inquiry-contact-actions { gap:16px; }
            .nm-inquiry-offer-grid { grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px; }
        }
    </style>

    <main class="nm-page nm-service-inquiry">
        <p class="nm-service-inquiry-offer">
            @if ($selectedServices === [] && $selectedProjects === [])
                General enquiry · choose an offer or project, or send a general request
            @elseif ($selectedServices === [])
                Project enquiry · {{ count($selectedProjects) }} project{{ count($selectedProjects) === 1 ? '' : 's' }}
            @elseif (count($selectedServices) === 1)
                {{ $service['category'] }} · {{ $service['status'] }}
            @else
                Private shortlist · {{ count($selectedServices) }} offers
            @endif
        </p>
        <h1>A private discussion.</h1>
        <p class="nm-service-inquiry-lead">
            @if ($isClientConnected)
                Your CRM client account is connected. Continue the request in the CRM with the offers you selected here.
            @elseif ($isStaffConnected)
                Your CRM team account is connected. Open the CRM to continue, or discuss the selected offers on WhatsApp.
            @else
                Create a CRM client account to continue, or sign in if you already have one. Your selected offers will remain here when you return.
            @endif
        </p>

        <section class="nm-service-inquiry-selection" aria-labelledby="nm-service-inquiry-selection-title" data-inquiry-selection @if ($selectedServices === []) hidden @endif>
            <h2 id="nm-service-inquiry-selection-title">Your selected offers <span data-inquiry-selection-count>{{ count($selectedServices) }}</span></h2>
            <p>These offers are synced with your shortlist. Remove any that you do not want included in this request.</p>
            <div class="nm-inquiry-offer-grid" id="nm-service-inquiry-selected" data-inquiry-selected-grid aria-label="Offers selected for this enquiry">
                @foreach ($selectedServices as $selectedOffer)
                    <x-studio::inquiry-offer-card :service="$selectedOffer" />
                @endforeach
            </div>
            <a href="{{ simple_site('studio', 'shortlist') }}">View your shortlist</a>
        </section>

        @if ($selectedProjects !== [])
            <section class="nm-service-inquiry-projects" aria-labelledby="nm-service-inquiry-projects-title">
                <h2 id="nm-service-inquiry-projects-title">Project context</h2>
                <ul>
                    @foreach ($selectedProjects as $selectedProject)
                        <li>
                            <span>{{ $selectedProject['name'] }} · {{ $selectedProject['status'] }}</span>
                            <form action="{{ simple_site('studio', 'service-inquiry/project/remove') }}" method="post">
                                @csrf
                                <input type="hidden" name="project" value="{{ $selectedProject['slug'] }}">
                                <button type="submit" aria-label="Remove {{ $selectedProject['name'] }} from this request">Remove</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($recommendedServices !== [])
            <section class="nm-service-inquiry-recommendations" aria-labelledby="nm-service-inquiry-recommendations-title">
                <h2 id="nm-service-inquiry-recommendations-title">{{ $selectedServices === [] ? 'Start with an offer' : 'Other offers that may fit' }}</h2>
                <p>Add an offer to your shortlist and it will be included in this request. You can choose more than one.</p>
                <div class="nm-inquiry-offer-grid" id="nm-service-inquiry-recommended" data-inquiry-recommended-grid aria-label="Recommended services to add">
                    @foreach ($recommendedServices as $recommendedOffer)
                        <x-studio::inquiry-offer-card :service="$recommendedOffer" />
                    @endforeach
                </div>
                <p><a href="{{ simple_site('studio', 'services') }}">Browse all services</a></p>
            </section>
        @endif

        <section class="nm-service-inquiry-contact" aria-labelledby="nm-service-inquiry-contact-title">
            @if ($isClientConnected)
                <h2 id="nm-service-inquiry-contact-title">Your client account is connected</h2>
                <p>Your selected offers are ready. Continue your request in the CRM, or discuss them with Numerimondes on WhatsApp.</p>
            @elseif ($isStaffConnected)
                <h2 id="nm-service-inquiry-contact-title">Your team account is connected</h2>
                <p>Your selected offers are ready. Open the CRM administration or discuss them with Numerimondes on WhatsApp.</p>
            @else
                <h2 id="nm-service-inquiry-contact-title">Continue with a client account</h2>
                <p>Create your CRM client account to continue with these selected offers. Already have an account? Sign in, then return here and refresh this page.</p>
            @endif
            <div class="nm-service-inquiry-contact-actions">
                @if ($isClientConnected)
                    <a href="{{ $crmConfig['client_request'] ?? $crmConfig['client_space'] ?? '#' }}" target="_blank" rel="noreferrer">Open client request <x-webkernel::icon icon="arrow-up-right" set="lucide" width="14" height="14" class="nm-icon-arrow" aria-hidden="true" /></a>
                    <a href="{{ $crmConfig['client_space'] ?? '#' }}" target="_blank" rel="noreferrer">Client space</a>
                @elseif ($isStaffConnected)
                    <a href="{{ $crmConfig['staff_login'] ?? '#' }}" target="_blank" rel="noreferrer">Open CRM administration <x-webkernel::icon icon="arrow-up-right" set="lucide" width="14" height="14" class="nm-icon-arrow" aria-hidden="true" /></a>
                @else
                    <a href="{{ $crmConfig['client_registration'] ?? '#' }}" target="_blank" rel="noreferrer">Create your client account <x-webkernel::icon icon="arrow-up-right" set="lucide" width="14" height="14" class="nm-icon-arrow" aria-hidden="true" /></a>
                    <a href="{{ $crmConfig['client_space'] ?? '#' }}" target="_blank" rel="noreferrer">Sign in</a>
                @endif
                <a href="{{ $whatsappHref }}" target="_blank" rel="noreferrer">Continue on WhatsApp <x-webkernel::icon icon="arrow-up-right" set="lucide" width="14" height="14" class="nm-icon-arrow" aria-hidden="true" /></a>
            </div>

            <x-webkernel-platform::section heading="Request a private discussion" class="mt-8">
                <p>Your selected offers and their current prices will be attached to the request. This profile selector stays anonymous; contact details are requested only when you choose to send an enquiry.</p>
                @if ($inquiryErrorMessage !== '')
                    <p class="nm-service-request-error" role="alert">{{ $inquiryErrorMessage }}</p>
                @endif
                <form class="nm-service-request-form" action="{{ simple_site('studio', 'service-inquiry') }}" method="post">
                    @csrf
                    <label class="nm-service-request-trap" aria-hidden="true">Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                    @if (array_filter($selectedServices, static fn (array $offer): bool => is_string($offer['quantity_unit'] ?? null)))
                        <fieldset class="nm-service-request-grid" style="margin:0;padding:0;border:0">
                            <legend>Quantities for per-unit offers</legend>
                            @foreach ($selectedServices as $selectedOffer)
                                @if (is_string($selectedOffer['quantity_unit'] ?? null))
                                    <label class="nm-service-request-field" for="service-request-quantity-{{ $selectedOffer['id'] }}">{{ __t($selectedOffer['name'] ?? []) }} · quantity ({{ $selectedOffer['quantity_unit'] }})
                                        <input id="service-request-quantity-{{ $selectedOffer['id'] }}" type="number" name="quantities[{{ $selectedOffer['id'] }}]" min="1" max="1000" step="1" value="1" required>
                                    </label>
                                @endif
                            @endforeach
                        </fieldset>
                    @endif
                    <div class="nm-service-request-grid">
                        <label class="nm-service-request-field" for="service-request-name">Name <span aria-hidden="true">*</span><input id="service-request-name" name="name" autocomplete="name" maxlength="120" required></label>
                        <label class="nm-service-request-field" for="service-request-email">Email <span aria-hidden="true">*</span><input id="service-request-email" name="email" type="email" autocomplete="email" maxlength="254" required></label>
                        <label class="nm-service-request-field" for="service-request-company">Company<input id="service-request-company" name="company" autocomplete="organization" maxlength="160"></label>
                        <label class="nm-service-request-field" for="service-request-phone">Phone<input id="service-request-phone" name="phone" type="tel" autocomplete="tel" maxlength="80"></label>
                        <label class="nm-service-request-field" for="service-request-currency">Quote currency
                            <select id="service-request-currency" name="quote_currency">
                                @foreach (($currencySettings['options'] ?? []) as $currencyCode => $currencyOption)
                                    <option value="{{ $currencyCode }}" @selected($currencyCode === $displayCurrency)>{{ $currencyCode }} · {{ $currencyOption['label'] ?? $currencyCode }}</option>
                                @endforeach
                            </select>
                        </label>
                        @if ($recruitmentSelected)
                            <label class="nm-service-request-field" for="service-request-role">Role to recruit <span aria-hidden="true">*</span><input id="service-request-role" name="recruitment_role" maxlength="160" required></label>
                            <label class="nm-service-request-field" for="service-request-location">Work location <span aria-hidden="true">*</span><input id="service-request-location" name="recruitment_location" maxlength="160" required></label>
                            <label class="nm-service-request-field" for="service-request-salary">Monthly salary basis (optional)<input id="service-request-salary" name="monthly_salary" type="number" min="1" max="1000000000" step="0.01" inputmode="decimal"></label>
                            <input type="hidden" name="salary_currency" value="{{ $displayCurrency }}">
                        @endif
                    </div>
                    <label class="nm-service-request-field" for="service-request-message">What would you like to achieve? <span aria-hidden="true">*</span><textarea id="service-request-message" name="message" minlength="12" maxlength="5000" required></textarea></label>
                    <label style="display:flex;align-items:flex-start;gap:10px"><input type="checkbox" name="privacy_consent" value="1" required><span>I agree that Numerimondes may use these details to respond to this enquiry. Read the <a href="{{ simple_site('studio', 'privacy') }}">privacy notice</a>.</span></label>
                    <x-webkernel-platform::button type="submit" color="primary">Send enquiry</x-webkernel-platform::button>
                </form>
            </x-webkernel-platform::section>
        </section>
    </main>
</x-studio::page>
