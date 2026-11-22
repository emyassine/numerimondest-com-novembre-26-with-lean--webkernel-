@props(['canonical' => '', 'locale' => 'en'])

@php
    $serviceCatalog = \App\Studio\ServiceCatalog::all();
    $selectedSlugs = \App\Studio\ServiceInquiryController::selectedSlugs();
    $selectedServices = array_values(array_filter(
        array_map(static fn (string $slug): ?array => $serviceCatalog[$slug] ?? null, $selectedSlugs),
        'is_array',
    ));
    $projectCatalog = \App\Studio\ProjectCatalog::all();
    $selectedProjectSlugs = \App\Studio\ServiceInquiryController::selectedProjects();
    $selectedProjects = array_values(array_filter(
        array_map(static fn (string $slug): ?array => $projectCatalog[$slug] ?? null, $selectedProjectSlugs),
        'is_array',
    ));
    $allServices = array_values($serviceCatalog);
    $recommendedSlugs = [];
    foreach ($selectedServices as $selectedService) {
        foreach (($selectedService['related_slugs'] ?? []) as $relatedSlug) {
            if (is_string($relatedSlug) && isset($serviceCatalog[$relatedSlug]) && ! in_array($relatedSlug, $selectedSlugs, true)) {
                $recommendedSlugs[] = $relatedSlug;
            }
        }
    }
    foreach ($allServices as $candidate) {
        if (in_array($candidate['project_slug'] ?? null, $selectedProjectSlugs, true)
            && ! in_array($candidate['slug'], $selectedSlugs, true)) {
            $recommendedSlugs[] = $candidate['slug'];
        }
    }
    if ($recommendedSlugs === []) {
        foreach ($allServices as $candidate) {
            if (! empty($candidate['featured'])) {
                $recommendedSlugs[] = $candidate['slug'];
            }
        }
    }
    if ($recommendedSlugs === []) {
        $recommendedSlugs = array_column(array_slice($allServices, 0, 4), 'slug');
    }
    $recommendedServices = array_values(array_filter(
        array_map(static fn (string $slug): ?array => $serviceCatalog[$slug] ?? null, array_unique($recommendedSlugs)),
        static fn (?array $offer): bool => is_array($offer) && ! in_array($offer['slug'], $selectedSlugs, true),
    ));
    if ($recommendedServices === []) {
        $recommendedServices = array_values(array_filter(
            $allServices,
            static fn (array $offer): bool => ! in_array($offer['slug'], $selectedSlugs, true),
        ));
    }
    $recommendedServices = array_slice($recommendedServices, 0, 3);
    $service = $selectedServices[0] ?? null;
    $contextNames = array_map(
        static fn (string $name): string => preg_replace('/\s*·\s*/u', ' ', $name) ?? $name,
        [...array_column($selectedServices, 'name'), ...array_column($selectedProjects, 'name')],
    );
    $contextLabel = match (count($contextNames)) {
        0 => '',
        1 => $contextNames[0],
        2 => $contextNames[0].' and '.$contextNames[1],
        default => implode(', ', array_slice($contextNames, 0, -1)).', and '.end($contextNames),
    };
    $studioConfig = config('nm-studio', []);
    $crmConfig = is_array($studioConfig['crm'] ?? null) ? $studioConfig['crm'] : [];
    $accountStatus = \App\Studio\PerfexAccountSession::current();
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

<x-studio::website
    title="A private discussion — Numerimondes"
    description="Share the services you are considering and request a personal follow-up from Numerimondes."
    :canonical="$canonical"
    :locale="$locale"
    :site-ui="$site_ui ?? []"
>
    <style>
        .nm-service-inquiry { width:100%; max-width:1040px; margin:0 auto; padding:180px 24px 120px; }
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
        @media (max-width:700px) {
            .nm-service-inquiry { padding:130px 20px 75px; }
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
        </section>
    </main>
</x-studio::website>
