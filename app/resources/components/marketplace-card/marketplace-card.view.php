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
.nm-marketplace-card:hover { transform: translateY(-2px); }
.nm-marketplace-card-link:hover .nm-marketplace-card-image { transform: scale(1.018); }
.nm-marketplace-card-modal-content > a:hover { text-decoration: underline; text-underline-offset: .2em; }

@media (max-width: 700px) {
  .nm-marketplace-card-image-wrap { aspect-ratio:1.8 !important; }
  .nm-marketplace-card-content { padding:16px !important; }
  .nm-marketplace-card-status { font-size:8px !important; }
  .nm-marketplace-card-heading h3 { font-size:18px !important; }
  .nm-marketplace-card-footer { padding:0 16px 16px !important; }
}

@media (prefers-reduced-motion: reduce) {
  .nm-marketplace-card,
  .nm-marketplace-card-image { transition: none; }
}
</style>
<x-webkernel-platform::section
    :class="$card_classes"
    style="min-width:0;overflow:hidden;border-radius:var(--radius-md,.375rem)"
    :data-engagement="$engagement_slug"
    :data-priced="$is_priced"
    :data-search="$search_text"
>
    <a class="nm-marketplace-card-link" href="{{ $service['href'] }}" style="position:relative;height:100%;display:flex;flex-direction:column;color:var(--nm-ink);text-decoration:none">
        <div class="nm-marketplace-card-image-wrap" style="position:relative;min-height:125px;aspect-ratio:2.15;overflow:hidden;background:linear-gradient(135deg,#e2e8f0,#f8fafc)">
            <img
                class="nm-marketplace-card-image"
                src="{{ $service['image'] }}"
                alt="{{ $service['image_alt'] ?? $service['name'] }}"
                width="{{ $service['image_width'] ?? 1200 }}"
                height="{{ $service['image_height'] ?? 800 }}"
                loading="lazy"
                style="display:block;width:100%;height:100%;object-fit:cover;transition:transform .55s ease"
            >
            <span style="position:absolute;left:16px;bottom:14px;z-index:1">
                <x-webkernel-platform::badge color="gray" size="sm">{{ $service['category'] }}</x-webkernel-platform::badge>
            </span>
        </div>
        <div class="nm-marketplace-card-content" style="padding:20px;display:flex;flex:1;flex-direction:column;gap:12px">
            <h3 class="nm-marketplace-card-heading" style="margin:0;font:400 var(--nm-text-leading)/1.25 var(--nm-title);letter-spacing:.01em">{{ $service['name'] }}</h3>
            <p class="nm-marketplace-card-scope" style="flex:1;margin:0 0 8px;color:var(--text-secondary,#475569);font:400 var(--nm-text-size)/1.5 var(--nm-body)">{{ $service['card_description'] }}</p>
            @if (! empty($service['price_display']))
                <p class="nm-marketplace-card-price" style="margin:0;padding-top:13px;border-top:1px solid var(--border-subtle,#e2e8f0);font:600 14px/1.4 var(--nm-body)">{{ $service['price_display'] }}</p>
            @endif
        </div>
    </a>
    <div class="nm-marketplace-card-footer" style="display:grid;gap:12px;padding:0 20px 20px">
        <p class="nm-marketplace-card-status" style="margin:0;color:#065f46;font:400 13px/1.4 var(--nm-body)">{{ $service['status'] }} · {{ $service['format'] }}</p>
        <div class="nm-marketplace-card-actions" style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
            <x-webkernel-platform::button tag="a" :href="$service['href']" color="gray" size="sm" style="width:100%;justify-content:center">View offer</x-webkernel-platform::button>
            <x-webkernel-platform::button type="button" color="primary" size="sm" style="width:100%;justify-content:center" x-on:click="$el.closest('.nm-marketplace-card').querySelector('.fi-modal').dispatchEvent(new CustomEvent('open-modal'))">Details</x-webkernel-platform::button>
        </div>
    </div>
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
