@props([
    'industries' => [],
    'business_needs' => [],
])

@php
    $featuredIndustrySlugs = [
        'retail', 'private-equity', 'advanced-manufacturing-services', 'technology', 'oil-gas',
        'healthcare-life-sciences', 'chemicals', 'consumer-products', 'mining', 'financial-services',
    ];
    $featuredIndustries = [];
    foreach ($featuredIndustrySlugs as $slug) {
        if (isset($industries[$slug])) {
            $featuredIndustries[$slug] = $industries[$slug];
        }
    }
    $otherIndustries = array_diff_key($industries, $featuredIndustries);
    $featuredNeeds = array_slice($business_needs, 0, 10, true);
    $otherNeeds = array_slice($business_needs, 10, null, true);
    $industryGrid = (new \Webkernel\Platform\Support\View\ComponentAttributeBag())->grid([
        'default' => 1,
        'sm' => 2,
        'lg' => 3,
    ])->style('gap: .75rem');
    $expandedGrid = (new \Webkernel\Platform\Support\View\ComponentAttributeBag())->grid([
        'default' => 1,
        'sm' => 2,
        'lg' => 3,
    ])->style('gap: .75rem; margin-top: .75rem');
@endphp

<x-webkernel-platform::section heading="Answer two questions and put our thinking to work on your challenges." style="margin-bottom:2rem">
    <form id="nm-service-fit-selector" action="/services/recommendations" method="post">
        @csrf
        <fieldset style="margin:0 0 1.5rem;padding:0;border:0">
            <legend style="margin:0 0 1rem;font:500 1rem/1.4 var(--nm-body)">1. What is your industry? <span style="color:var(--text-secondary,#64748b)">Question 1 of 2</span></legend>
            <div {{ $industryGrid }}>
                @foreach ($featuredIndustries as $slug => $label)
                    <label for="service-industry-{{ $slug }}" style="display:flex;align-items:center;gap:.75rem;min-height:48px;padding:.7rem .8rem;border:1px solid var(--border-default,#cbd5e1);border-radius:var(--radius-md,.375rem);background:var(--surface-panel,#fff);cursor:pointer">
                        <x-webkernel-platform::input.radio id="service-industry-{{ $slug }}" name="industry" :value="$slug"></x-webkernel-platform::input.radio>
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>
            @if ($otherIndustries !== [])
                <details style="margin-top:.75rem">
                    <summary style="cursor:pointer">View all industries</summary>
                    <div {{ $expandedGrid }}>
                        @foreach ($otherIndustries as $slug => $label)
                            <label for="service-industry-{{ $slug }}" style="display:flex;align-items:center;gap:.75rem;min-height:48px;padding:.7rem .8rem;border:1px solid var(--border-default,#cbd5e1);border-radius:var(--radius-md,.375rem);background:var(--surface-panel,#fff);cursor:pointer">
                            <x-webkernel-platform::input.radio id="service-industry-{{ $slug }}" name="industry" :value="$slug"></x-webkernel-platform::input.radio>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </details>
            @endif
        </fieldset>

        <fieldset style="margin:0 0 1.25rem;padding:0;border:0">
            <legend style="margin:0 0 1rem;font:500 1rem/1.4 var(--nm-body)">2. What is your business need? <span style="color:var(--text-secondary,#64748b)">Question 2 of 2</span></legend>
            <div {{ $industryGrid }}>
                @foreach ($featuredNeeds as $slug => $label)
                    <label for="service-need-{{ $slug }}" style="display:flex;align-items:center;gap:.75rem;min-height:48px;padding:.7rem .8rem;border:1px solid var(--border-default,#cbd5e1);border-radius:var(--radius-md,.375rem);background:var(--surface-panel,#fff);cursor:pointer">
                        <x-webkernel-platform::input.radio id="service-need-{{ $slug }}" name="business_need" :value="$slug"></x-webkernel-platform::input.radio>
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>
            @if ($otherNeeds !== [])
                <details style="margin-top:.75rem">
                    <summary style="cursor:pointer">View all business needs</summary>
                    <div {{ $expandedGrid }}>
                        @foreach ($otherNeeds as $slug => $label)
                            <label for="service-need-{{ $slug }}" style="display:flex;align-items:center;gap:.75rem;min-height:48px;padding:.7rem .8rem;border:1px solid var(--border-default,#cbd5e1);border-radius:var(--radius-md,.375rem);background:var(--surface-panel,#fff);cursor:pointer">
                                <x-webkernel-platform::input.radio id="service-need-{{ $slug }}" name="business_need" :value="$slug"></x-webkernel-platform::input.radio>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </details>
            @endif
        </fieldset>

        <details style="margin:0 0 1.25rem">
            <summary style="cursor:pointer">Where are you in your business journey? <span style="color:var(--text-secondary,#64748b)">Optional</span></summary>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:.75rem;margin-top:1rem">
                <label>Full-time team size<select name="team_size"><option value="">Any</option><option value="solo">Solo</option><option value="2-10">2–10</option><option value="11-50">11–50</option><option value="51+">51+</option></select></label>
                <label>Annual revenue<select name="annual_revenue"><option value="">Prefer not to say</option><option value="under-1m">Under 1M MAD</option><option value="1m-10m">1–10M MAD</option><option value="10m-50m">10–50M MAD</option><option value="50m+">50M+ MAD</option></select></label>
                <label>Net profit margin<select name="profit_margin"><option value="">Prefer not to say</option><option value="negative">Below 0%</option><option value="0-10">0–10%</option><option value="11-25">11–25%</option><option value="26+">26%+</option></select></label>
                <label>Ownership<select name="ownership"><option value="">Any</option><option value="solo">Solo owner</option><option value="partners">With partners</option></select></label>
                <label>When did you discover Numerimondes?<select name="discovery"><option value="">Prefer not to say</option><option value="recent">Recently</option><option value="months">A few months ago</option><option value="year+">Over a year ago</option></select></label>
            </div>
        </details>

        <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap">
            <x-webkernel-platform::button type="submit" color="primary">See recommendations</x-webkernel-platform::button>
            <x-webkernel-platform::button type="button" color="gray" outlined data-reset-fit hidden>Reset</x-webkernel-platform::button>
        </div>
    </form>

    <div id="nm-service-fit-result" role="status" aria-live="polite" hidden style="margin-top:1.25rem;padding-top:1rem;border-top:1px solid var(--border-default,#cbd5e1)">
        <p id="nm-service-fit-summary" style="margin:0 0 .75rem"></p>
        <h3 style="margin:0 0 .75rem">Recommended for you</h3>
        <ul id="nm-service-fit-recommendations" style="margin:0;padding-left:1.25rem"></ul>
        <p style="margin:.75rem 0 0">Explore all offerings below. Select an industry or business need to refine your recommendations.</p>
    </div>
</x-webkernel-platform::section>
