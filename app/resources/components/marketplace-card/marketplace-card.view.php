@props(['service' => [], 'card_class' => ''])
@php
    $card_classes = implode(' ', array_filter([
        'nm-marketplace-card',
        $service['class'] ?? '',
        $card_class,
    ]));
    $engagement_slug = \Webkernel\Utilities\Str::slug($service['engagement_type'] ?? 'Project');
    $is_priced = ! empty($service['price_display']) ? 'true' : 'false';
    $search_text = mb_strtolower(implode(' ', array_filter([
        $service['name'],
        $service['category'],
        $service['card_description'],
        $service['format'],
        $service['price_display'] ?? '',
        $service['brand'] ?? '',
    ])));
@endphp
<style>
.nm-marketplace-card.is-removing { opacity: 0; transform: translateY(5px); }
.nm-marketplace-card-link:hover .nm-marketplace-card-image { transform: scale(1.018); }
.nm-marketplace-card-modal-content > a:hover { text-decoration: underline; text-underline-offset: .2em; }

@media (max-width: 700px) {
  .nm-marketplace-card-image-wrap { aspect-ratio:1.8 !important; }
  .nm-marketplace-card-content { min-height:125px !important; padding:16px !important; }
  .nm-marketplace-card-footer { padding:0 16px 16px !important; }
  .nm-marketplace-card-category,
  .nm-marketplace-card-status,
  .nm-marketplace-card-cta { font-size:8px !important; }
  .nm-marketplace-card-heading h2 { font-size:12px !important; }
  .nm-marketplace-card-price { font-size:14px !important; }
}

@media (prefers-reduced-motion: reduce) {
  .nm-marketplace-card,
  .nm-marketplace-card-image { transition: none; }
}
</style>
<x-webkernel-platform::section
    :class="$card_classes"
    :has-content-el="false"
    :data-engagement="$engagement_slug"
    :data-priced="$is_priced"
    :data-search="$search_text"
>
    <a class="nm-marketplace-card-link" href="{{ $service['href'] }}" style="position:relative;height:100%;display:flex;flex-direction:column;color:var(--nm-ink);text-decoration:none">
        <div class="nm-marketplace-card-image-wrap" style="aspect-ratio:2.15;overflow:hidden;background:#e7e3db">
            <img
                class="nm-marketplace-card-image"
                src="{{ $service['image'] }}"
                alt="{{ $service['image_alt'] ?? $service['name'] }}"
                width="{{ $service['image_width'] ?? 1200 }}"
                height="{{ $service['image_height'] ?? 800 }}"
                loading="lazy"
                style="display:block;width:100%;height:100%;object-fit:cover;transition:transform .55s ease"
            >
        </div>
        <div class="nm-marketplace-card-content" style="min-height:132px;padding:17px 20px 16px;display:flex;flex:1;flex-direction:column;justify-content:space-between;gap:12px">
            <div class="nm-marketplace-card-heading">
                <p class="nm-marketplace-card-category" style="margin:0;font:400 10px/1.35 var(--nm-body);letter-spacing:.14em;text-transform:uppercase">{{ $service['category'] }}</p>
                <h2 style="margin:7px 0 0;font:400 13px/1.3 var(--nm-title);letter-spacing:.08em;text-transform:uppercase">{{ $service['name'] }}</h2>
                @if (! empty($service['price_display']))
                    <p class="nm-marketplace-card-price" style="margin:7px 0 0;font:400 12px/1.35 var(--nm-body)">{{ $service['price_display'] }}</p>
                @endif
            </div>
        </div>
    </a>
    <div class="nm-marketplace-card-footer" style="display:flex;align-items:center;justify-content:space-between;gap:20px;padding:0 20px 16px">
        <span class="nm-marketplace-card-status" style="margin:0;color:#74716b;font:400 10px/1.35 var(--nm-body);letter-spacing:.14em;text-transform:uppercase">{{ $service['status'] }}</span>
        <div class="nm-marketplace-card-actions" style="display:flex;align-items:center;gap:9px">
            <a class="nm-marketplace-card-cta" href="{{ $service['href'] }}" style="display:inline-flex;align-items:center;gap:5px;white-space:nowrap;font:400 10px/1.35 var(--nm-body);letter-spacing:.14em;text-decoration:none;text-transform:uppercase">View offer <x-webkernel::icon icon="arrow-up-right" set="lucide" width="14" height="14" class="nm-icon-arrow" aria-hidden="true" /></a>
            <x-webkernel-platform::icon-button
                :icon="\Webkernel\Platform\Support\Icons\Heroicon::OutlinedInformationCircle"
                :label="'Details for '.$service['name']"
                tooltip="View offer details"
                color="gray"
                size="sm"
                style="width:30px;height:30px;min-width:30px"
                x-on:click="$el.closest('.nm-marketplace-card').querySelector('.fi-modal').dispatchEvent(new CustomEvent('open-modal'))"
            />
        </div>
    </div>
    <x-studio::shortlist-button :service="$service" />

    <x-webkernel-platform::modal :heading="$service['name']" :description="$service['category']" width="lg">
        <div class="nm-marketplace-card-modal-content" style="display:grid;gap:16px">
            <p style="margin:0;font:400 16px/1.5 var(--nm-body)">{{ $service['card_description'] }}</p>
            <p class="nm-marketplace-card-modal-format" style="margin:0;color:var(--text-secondary,#77746e);font:400 11px/1.5 var(--nm-body);letter-spacing:.08em;text-transform:uppercase">{{ $service['format'] }}</p>
            @if (! empty($service['price_display']))
                <p class="nm-marketplace-card-modal-price" style="margin:0;font:400 14px/1.4 var(--nm-body)">{{ $service['price_display'] }}</p>
            @endif
            @if (! empty($service['brand']))
                <p class="nm-marketplace-card-modal-brand" style="margin:0;color:var(--text-secondary,#77746e);font:400 11px/1.5 var(--nm-body);letter-spacing:.08em;text-transform:uppercase">{{ $service['brand'] }}</p>
            @endif
            <a href="{{ $service['href'] }}" style="width:fit-content;display:inline-flex;align-items:center;gap:5px;color:var(--primary-600);font:400 10px/1.4 var(--nm-body);letter-spacing:.12em;text-decoration:none;text-transform:uppercase">View full offer <x-webkernel::icon icon="arrow-up-right" set="lucide" width="14" height="14" aria-hidden="true" /></a>
        </div>
    </x-webkernel-platform::modal>
</x-webkernel-platform::section>
