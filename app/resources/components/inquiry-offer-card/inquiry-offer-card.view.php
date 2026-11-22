@props(['service' => []])

<x-webkernel-platform::card compact class="nm-inquiry-offer-card">
    <article>
        <a class="nm-inquiry-offer-card-link" href="{{ $service['href'] }}">
            <p class="nm-inquiry-offer-card-category">{{ $service['category'] }}</p>
            <h3>{{ $service['name'] }}</h3>
            <p class="nm-inquiry-offer-card-meta">
                @if (! empty($service['price_display'])){{ $service['price_display'] }} · @endif{{ $service['format'] }}
            </p>
        </a>
        <x-studio::shortlist-button :service="$service" :compact="true" />
    </article>
</x-webkernel-platform::card>
