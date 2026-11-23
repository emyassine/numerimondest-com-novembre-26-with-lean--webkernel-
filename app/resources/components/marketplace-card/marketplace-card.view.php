@props(['service' => []])
<style>
.nm-marketplace-card {
  position: relative;
  min-width: 0;
  overflow: hidden;
  border: 1px solid var(--border-default, var(--nm-line));
  border-radius: var(--radius-md, .375rem);
  background: var(--surface-panel, #fff);
  box-shadow: 0 1px 2px rgba(15, 23, 42, .06), 0 8px 24px rgba(15, 23, 42, .04);
  transition: opacity .18s ease, transform .18s ease;
}
.nm-marketplace-card.is-removing { opacity: 0; transform: translateY(5px); }

.nm-marketplace-card-link {
  position: relative;
  height: 100%;
  display: flex;
  flex-direction: column;
  color: var(--nm-ink);
  text-decoration: none;
}

.nm-marketplace-card-image-wrap {
  aspect-ratio: 2.15;
  overflow: hidden;
  background: #e7e3db;
}

.nm-marketplace-card-image {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform .55s ease;
}

.nm-marketplace-card-link:hover .nm-marketplace-card-image { transform: scale(1.018); }

.nm-marketplace-card-content {
  min-height: 132px;
  padding: 17px 20px 16px;
  display: flex;
  flex: 1;
  flex-direction: column;
  justify-content: space-between;
  gap: 12px;
}

.nm-marketplace-card-category,
.nm-marketplace-card-status,
.nm-marketplace-card-cta {
  margin: 0;
  font: 400 10px/1.35 var(--nm-body);
  letter-spacing: .14em;
  text-transform: uppercase;
}

.nm-marketplace-card-heading h2 {
  margin: 7px 0 0;
  font: 400 13px/1.3 var(--nm-title);
  letter-spacing: .08em;
  text-transform: uppercase;
}

.nm-marketplace-card-price {
  margin: 7px 0 0;
  font: 400 12px/1.35 var(--nm-body);
}

.nm-marketplace-card-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  padding: 0 20px 16px;
}

.nm-marketplace-card-status { color: #74716b; }
.nm-marketplace-card-actions {
  display: flex;
  align-items: center;
  gap: 9px;
}
.nm-marketplace-card-cta {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  white-space: nowrap;
  font: 400 10px/1.35 var(--nm-body);
  letter-spacing: .14em;
  text-decoration: none;
  text-transform: uppercase;
}
.nm-marketplace-card-actions .fi-icon-btn {
  width: 30px;
  height: 30px;
  min-width: 30px;
}
.nm-marketplace-card-modal-content { display: grid; gap: 16px; }
.nm-marketplace-card-modal-content p { margin: 0; font: 400 16px/1.5 var(--nm-body); }
.nm-marketplace-card-modal-content .nm-marketplace-card-modal-format,
.nm-marketplace-card-modal-content .nm-marketplace-card-modal-brand { color: var(--text-secondary, #77746e); font: 400 11px/1.5 var(--nm-body); letter-spacing: .08em; text-transform: uppercase; }
.nm-marketplace-card-modal-content .nm-marketplace-card-modal-price { font: 400 14px/1.4 var(--nm-body); }
.nm-marketplace-card-modal-content > a { width: fit-content; display: inline-flex; align-items: center; gap: 5px; color: var(--primary-600); font: 400 10px/1.4 var(--nm-body); letter-spacing: .12em; text-decoration: none; text-transform: uppercase; }
.nm-marketplace-card-modal-content > a:hover { text-decoration: underline; text-underline-offset: .2em; }

@media (max-width: 700px) {
  .nm-marketplace-card-image-wrap { aspect-ratio: 1.8; }
  .nm-marketplace-card-content { min-height: 125px; padding: 16px; }
  .nm-marketplace-card-footer { padding: 0 16px 16px; }
  .nm-marketplace-card-category,
  .nm-marketplace-card-status,
  .nm-marketplace-card-cta { font-size: 8px; }
  .nm-marketplace-card-heading h2 { font-size: 12px; }
  .nm-marketplace-card-price { font-size: 14px; }
}

@media (prefers-reduced-motion: reduce) {
  .nm-marketplace-card,
  .nm-marketplace-card-image { transition: none; }
}
</style>
<article
    class="nm-marketplace-card {{ $service['class'] }}"
    data-engagement="{{ \Webkernel\Utilities\Str::slug($service['engagement_type'] ?? 'Project') }}"
    data-priced="{{ ! empty($service['price_display']) ? 'true' : 'false' }}"
    data-search="{{ mb_strtolower(implode(' ', array_filter([
        $service['name'],
        $service['category'],
        $service['card_description'],
        $service['format'],
        $service['price_display'] ?? '',
        $service['brand'] ?? '',
    ]))) }}"
>
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
