@props([
    'service' => [],
])

@php
    $imagePath = base_path('app/resources/views/pages/'.($service['image_source'] ?? ''));
    $imageDimensions = is_file($imagePath) ? @getimagesize($imagePath) : false;
    $imageDimensions = $imageDimensions ?: [1200, 800];
    $relatedServices = [];
    foreach (($service['related_slugs'] ?? []) as $relatedSlug) {
        if (is_string($relatedSlug) && $relatedSlug !== ($service['slug'] ?? '')) {
            $related = \App\Studio\ServiceCatalog::all()[$relatedSlug] ?? null;
            if (is_array($related)) {
                $relatedServices[] = $related;
            }
        }
    }
@endphp

<x-studio::page
    class="bleed-page"
    :title="$service['name'].' — Numerimondes'"
    :description="$service['card_description']"
>
    <main class="nm-page nm-service-detail">
        <header class="nm-service-detail-intro">
            <p class="nm-service-detail-category">{{ $service['category'] }} <span aria-hidden="true">·</span> {{ $service['status'] }}</p>
            <h1>{{ $service['name'] }}</h1>
            <p class="nm-service-detail-lead">{{ $service['introduction'] }}</p>
            @if (! empty($service['price_display']))
                <p class="nm-service-detail-price">{{ $service['price_display'] }} <span>· final scope confirmed in writing</span></p>
            @endif
            <div class="nm-service-detail-actions">
                <x-studio::shortlist-button :service="$service" />
                <x-studio::service-inquiry-launch :services="[$service['slug']]" class="nm-service-detail-cta">
                    Request a private discussion <x-webkernel::icon icon="arrow-up-right" set="lucide" width="14" height="14" class="nm-icon-arrow" aria-hidden="true" />
                </x-studio::service-inquiry-launch>
            </div>
        </header>

        <figure class="nm-service-detail-image">
            <img
                src="{{ $service['image'] }}"
                alt="{{ $service['image_alt'] ?? $service['name'] }}"
                width="{{ $imageDimensions[0] }}"
                height="{{ $imageDimensions[1] }}"
                fetchpriority="high"
            >
        </figure>

        <div class="nm-service-detail-sections">
            @foreach ($service['sections'] as $section)
                <section class="nm-service-detail-section">
                    <h2>{{ $section['title'] }}</h2>
                    <div>
                        @foreach ($section['paragraphs'] ?? [] as $paragraph)
                            <p>{{ $paragraph }}</p>
                        @endforeach
                        @if (! empty($section['items']))
                            <ul>
                                @foreach ($section['items'] as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </section>
            @endforeach
        </div>

        @if ($relatedServices !== [])
            <section class="nm-service-detail-related" aria-labelledby="nm-service-related-title">
                <h2 id="nm-service-related-title">Services that work alongside this offer</h2>
                <x-studio::marketplace-list
                    :services="$relatedServices"
                    :id="'related-'.$service['slug']"
                    :label="'Related services for '.$service['name']"
                />
            </section>
        @endif

        <section class="nm-service-detail-close">
            <h2>A considered next step</h2>
            <p>Yassine, Numerimondes’ founder, and the account manager personally review each request. They will contact you to understand your priorities and identify the right next step.</p>
            <x-studio::service-inquiry-launch :services="[$service['slug']]" class="nm-service-detail-cta">Request a private discussion <x-webkernel::icon icon="arrow-up-right" set="lucide" width="14" height="14" class="nm-icon-arrow" aria-hidden="true" /></x-studio::service-inquiry-launch>
        </section>
    </main>
</x-studio::page>
