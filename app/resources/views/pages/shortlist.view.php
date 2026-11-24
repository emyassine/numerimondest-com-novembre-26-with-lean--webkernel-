@php
    $savedSlugs = \App\Studio\Shortlist\ServiceShortlist::all();
    $catalog = \App\Studio\Catalog\ServiceCatalog::all();
    $shortlistedServices = array_values(array_filter(
        array_map(static fn (string $slug): ?array => $catalog[$slug] ?? null, $savedSlugs),
        'is_array',
    ));
@endphp

<x-studio::page
    title="Your shortlist — Numerimondes"
    description="Keep selected Numerimondes services together and request a private discussion with the founder and account manager."
    robots="noindex,follow"
    rss="false"
>
    <style>
        .nm-shortlist-page { width:100%; max-width:1040px; min-height:70vh; margin:0 auto; padding:180px 0 120px; }
        .nm-shortlist-heading { max-width:760px; margin-bottom:48px; }
        .nm-shortlist-kicker { margin:0 0 18px; color:#77746e; font:400 10px/1.4 var(--nm-title); letter-spacing:.15em; text-transform:uppercase; }
        .nm-shortlist-heading h1 { margin:0 0 22px; font:400 clamp(18px,2.2vw,30px)/1.4 var(--nm-title); letter-spacing:.12em; text-transform:uppercase; }
        .nm-shortlist-heading p:last-child { margin:0; font:400 17px/1.5 var(--nm-body); }
        .nm-shortlist-empty { padding:55px 0; border-top:1px solid var(--border-default,var(--nm-line)); }
        .nm-shortlist-empty[hidden],[data-shortlist-cta][hidden] { display:none; }
        .nm-shortlist-empty p { margin:0 0 22px; font:400 17px/1.5 var(--nm-body); }
        .nm-shortlist-action { display:inline-flex; min-height:44px; align-items:center; gap:10px; padding:12px 15px; border:1px solid var(--border-default,var(--nm-line)); border-radius:var(--radius-md,.375rem); color:var(--text-primary,var(--nm-ink)); background:var(--surface-panel,#fff); box-shadow:0 1px 2px rgba(15,23,42,.06),0 8px 24px rgba(15,23,42,.04); font:400 9px/1.3 var(--nm-title); letter-spacing:.12em; text-decoration:none; text-transform:uppercase; }
        .nm-shortlist-action:hover { background:var(--surface-muted,#f1f5f9); }
        .nm-shortlist-cta { margin-top:34px; }
        .nm-inquiry-offer-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(min(100%,220px),1fr)); gap:10px; }
        @media (max-width:700px) {
            .nm-shortlist-page { padding:130px 0 75px; }
            .nm-shortlist-heading { margin-bottom:32px; }
            .nm-shortlist-kicker { font-size:8px; }
            .nm-shortlist-heading h1 { font-size:17px; }
            .nm-shortlist-heading p:last-child,.nm-shortlist-empty p { font-size:15px; }
            .nm-shortlist-action { font-size:8px; }
            .nm-inquiry-offer-grid { grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px; }
        }
    </style>

    <main class="nm-page nm-shortlist-page">
        <header class="nm-shortlist-heading">
            <p class="nm-shortlist-kicker">Numerimondes · Private shortlist</p>
            <h1>Your shortlist</h1>
            <p>Add or remove offers here or from a service page. Your shortlist stays in sync with the private discussion form, so the offers you choose travel with your request.</p>
        </header>

        <div class="nm-inquiry-offer-grid" id="nm-shortlist-services" data-shortlist-grid aria-label="Your saved service offers">
            @foreach ($shortlistedServices as $shortlistedService)
                <x-studio::inquiry-offer-card :service="$shortlistedService" />
            @endforeach
        </div>
        <div data-shortlist-cta @if ($shortlistedServices === []) hidden @endif>
            <x-studio::service-inquiry-launch :services="$shortlistedServices" class="nm-shortlist-action nm-shortlist-cta">
                Request a private discussion
                <x-webkernel::icon icon="arrow-up-right" set="lucide" width="14" height="14" aria-hidden="true" />
            </x-studio::service-inquiry-launch>
        </div>
        <div class="nm-shortlist-empty" data-shortlist-empty @if ($shortlistedServices !== []) hidden @endif>
            <p>Your shortlist is empty. Save an offer that feels relevant, and we can consider it together.</p>
            <a class="nm-shortlist-action" href="{{ simple_site('studio', 'services') }}">Explore services <x-webkernel::icon icon="arrow-up-right" set="lucide" width="14" height="14" aria-hidden="true" /></a>
        </div>
    </main>
</x-studio::page>
