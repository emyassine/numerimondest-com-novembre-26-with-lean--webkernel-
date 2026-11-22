@props(['service' => []])
<article class="nm-marketplace-card {{ $service['class'] }}">
    <a class="nm-marketplace-card-link" href="{{ $service['href'] }}">
        <div class="nm-marketplace-card-image-wrap">
            <img
                class="nm-marketplace-card-image"
                src="{{ $service['image'] }}"
                alt="{{ $service['image_alt'] ?? $service['name'] }}"
                width="{{ $service['image_width'] ?? 1200 }}"
                height="{{ $service['image_height'] ?? 800 }}"
                loading="lazy"
            >
        </div>
        <div class="nm-marketplace-card-content">
            <div class="nm-marketplace-card-heading">
                <p class="nm-marketplace-card-category">{{ $service['category'] }}</p>
                <h2>{{ $service['name'] }}</h2>
                @if (! empty($service['price_display']))
                    <p class="nm-marketplace-card-price">{{ $service['price_display'] }}</p>
                @endif
            </div>
        </div>
    </a>
    <div class="nm-marketplace-card-footer">
        <span class="nm-marketplace-card-status">{{ $service['status'] }}</span>
        <div class="nm-marketplace-card-actions">
            <a class="nm-marketplace-card-cta" href="{{ $service['href'] }}">View offer <x-webkernel::icon icon="arrow-up-right" set="lucide" width="14" height="14" class="nm-icon-arrow" aria-hidden="true" /></a>
            <x-webkernel-platform::icon-button
                :icon="\Webkernel\Platform\Support\Icons\Heroicon::OutlinedInformationCircle"
                :label="'Details for '.$service['name']"
                tooltip="View offer details"
                color="gray"
                size="sm"
                x-on:click="$el.closest('.nm-marketplace-card').querySelector('.fi-modal').dispatchEvent(new CustomEvent('open-modal'))"
            />
        </div>
    </div>
    <x-studio::shortlist-button :service="$service" />

    <x-webkernel-platform::modal heading="{{ $service['name'] }}" description="{{ $service['category'] }}" width="lg">
        <div class="nm-marketplace-card-modal-content">
            <p>{{ $service['card_description'] }}</p>
            <p class="nm-marketplace-card-modal-format">{{ $service['format'] }}</p>
            @if (! empty($service['price_display']))
                <p class="nm-marketplace-card-modal-price">{{ $service['price_display'] }}</p>
            @endif
            @if (! empty($service['brand']))
                <p class="nm-marketplace-card-modal-brand">{{ $service['brand'] }}</p>
            @endif
            <a href="{{ $service['href'] }}">View full offer <x-webkernel::icon icon="arrow-up-right" set="lucide" width="14" height="14" aria-hidden="true" /></a>
        </div>
    </x-webkernel-platform::modal>
</article>
