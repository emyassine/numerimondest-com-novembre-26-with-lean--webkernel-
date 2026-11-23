@props([
    'nav_items' => [],
    'shortlist_count' => 0,
    'currency' => 'MAD',
    'currency_options' => [],
    'currency_config' => [],
    'default_currency' => 'MAD',
    'ordered_currency_options' => [],
    'site_prefix' => '/',
    'request_path' => '/',
])

<style>
.nm-nav--compact { display: none; }
.nm-nav-menu-trigger { box-sizing: border-box; width: 44px; height: 44px; display: grid; place-items: center; padding: 0; border: 0; background: transparent; cursor: pointer; color: inherit; }
.nm-nav-menu-trigger svg { width: 26px; height: 26px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; }
.nm-mobile-currency-switcher {
  position: relative;
  min-height: 48px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 12px 18px;
  color: inherit;
  font: 500 14px/1.35 var(--nm-body);
  border-top: 1px solid var(--fi-dropdown-list-item-border-color, #eee);
  cursor: pointer;
}
.nm-mobile-currency-switcher-label { display: inline-flex; align-items: center; gap: 10px; }
.nm-mobile-currency-switcher-label svg { width: 16px; height: 16px; flex: 0 0 auto; }
@media (max-width: 900px) {
  .nm-nav--compact { grid-column: 3; justify-self: end; display: block; }
}
@media (max-width: 700px) {
  .nm-nav--compact { display: block; margin-left: auto; flex: 0 0 auto; z-index: 2; }
  .nm-nav-menu-trigger { flex: 0 0 44px; }
}
</style>

    <div class="nm-nav--compact">
        <x-webkernel-platform::dropdown
            placement="bottom-end"
            width="xs"
            offset="4"
            maxHeight="min(60dvh, 24rem)"
            shift
            teleport>
            <x-slot name="trigger">
                <button type="button" class="nm-nav-menu-trigger" aria-label="Open site menu" title="Open site menu">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 6h18M3 12h18M3 18h18" /></svg>
                </button>
            </x-slot>
            <nav aria-label="Main navigation">
                <x-webkernel-platform::dropdown.list>
                    @foreach (array_merge($nav_items['left'], $nav_items['right']) as $item)
                        @if (($item['type'] ?? null) !== 'currency')
                            <x-webkernel-platform::dropdown.list.item
                                tag="a"
                                :href="$item['href']"
                                :aria-current="$request_path === $item['href'] ? 'page' : null"
                                :data-shortlist-link="$item['key'] === 'shortlist' ? true : null"
                            >
                                <x-webkernel::icon :icon="$item['icon']" set="lucide" width="16" height="16" aria-hidden="true" />
                                {{ $item['mobile_label'] }}
                                @if ($item['key'] === 'shortlist')
                                    <x-slot name="badge">
                                        @if ($shortlist_count > 0)
                                            <span data-shortlist-badge><x-webkernel-platform::badge color="gray" size="sm"><span data-shortlist-count>{{ $shortlist_count }}</span></x-webkernel-platform::badge></span>
                                        @else
                                            <template data-shortlist-badge-template><span data-shortlist-badge><x-webkernel-platform::badge color="gray" size="sm"><span data-shortlist-count>0</span></x-webkernel-platform::badge></span></template>
                                        @endif
                                    </x-slot>
                                @endif
                            </x-webkernel-platform::dropdown.list.item>
                        @endif
                    @endforeach
                </x-webkernel-platform::dropdown.list>
            </nav>

            @if (count($currency_options) > 1)
                <div class="nm-mobile-currency-switcher" role="none">
                    <span class="nm-mobile-currency-switcher-label">
                        <x-webkernel::icon icon="coins" set="lucide" aria-hidden="true" />
                        <span>Currency</span>
                    </span>
                    <x-studio::currency-switcher
                        :currency="$currency"
                        :currency-options="$ordered_currency_options"
                        :default-currency="$default_currency"
                        :show-symbol="($currency_config['show_symbol'] ?? false) === true"
                        :site-prefix="$site_prefix"
                        :current-path="$request_path"
                        trigger-role="menuitem"
                        trigger-tabindex="-1"
                    />
                </div>
            @endif
        </x-webkernel-platform::dropdown>
    </div>

<script>
    document.querySelectorAll('.nm-mobile-currency-switcher').forEach((row) => {
        row.addEventListener('click', (event) => {
            if (event.target.closest('.nm-currency-switcher-trigger')) return;
            row.querySelector('.nm-currency-switcher-trigger')?.click();
        });
    });
</script>
