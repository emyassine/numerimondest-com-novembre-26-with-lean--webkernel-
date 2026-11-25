@props(['service' => []])

@php $priceDisplay = offering_price_display($service); @endphp

<x-webkernel-platform::card compact class="nm-inquiry-offer-card">
    <article>
        <a class="nm-inquiry-offer-card-link" href="{{ $service['href'] }}">
            <p class="nm-inquiry-offer-card-category">{{ $service['category'] }}</p>
            <h3>{{ __t($service['name'] ?? []) }}</h3>
            <p class="nm-inquiry-offer-card-meta">
                @if ($priceDisplay !== ''){{ $priceDisplay }} · @endif{{ $service['format'] }}
            </p>
        </a>
        <x-studio::shortlist-button :service="$service" :compact="true" />
    </article>
</x-webkernel-platform::card>
