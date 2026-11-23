
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
@php
    $mobile_nav_items = array_merge($nav_items['left'] ?? [], $nav_items['right'] ?? []);
    $mobile_nav_items = array_merge(
        array_filter($mobile_nav_items, static fn (array $item): bool => ($item['type'] ?? null) !== 'currency'),
        array_filter($mobile_nav_items, static fn (array $item): bool => ($item['type'] ?? null) === 'currency'),
    );
@endphp

<style>
.nm-nav--compact {
    display: none;
}

.nm-nav-menu-trigger {
    box-sizing: border-box;
    width: 44px;
    height: 44px;
    display: grid;
    place-items: center;
    padding: 0;
    border: 0;
    background: transparent;
    cursor: pointer;
    color: inherit;
}

.nm-nav-menu-trigger svg {
    width: 26px;
    height: 26px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
    stroke-linecap: round;
}

.nm-nav--compact [data-slot="list"] .fi-dropdown-list-item,
.nm-nav--compact .fi-dropdown-list-item,
.nm-nav--compact nav a {
    display: flex;
    align-items: center;
    flex-wrap: nowrap;
    gap: 10px;
    white-space: nowrap;
}

.nm-nav-item-row {
    display: inline-flex;
    align-items: center;
    flex: 0 0 auto;
    flex-wrap: nowrap;
    gap: 10px;
    white-space: nowrap;
    line-height: 1.25;
}

.nm-nav-item-row svg {
    width: 16px;
    height: 16px;
    flex: 0 0 auto;
    display: block;
}

.nm-nav-item-label {
    white-space: nowrap;
    line-height: 1.25;
}

.nm-nav--compact .nm-dropdown-currency-item {
    display: block !important;
    width: 100% !important;
    min-width: 0;
    box-sizing: border-box;
    padding: 0 !important;
    border-top: 1px solid var(
        --fi-dropdown-list-item-border-color,
        #eee
    );
}

.nm-nav--compact .nm-dropdown-currency-item .fi-dropdown,
.nm-nav--compact .nm-dropdown-currency-item .fi-dropdown-trigger,
.nm-nav--compact .nm-dropdown-currency-item > div {
    display: block !important;
    width: 100% !important;
}

@media (max-width: 900px) {
    .nm-nav--compact {
        grid-column: 3;
        justify-self: end;
        display: block;
    }
}

@media (max-width: 700px) {
    .nm-nav--compact {
        display: block;
        margin-left: auto;
        flex: 0 0 auto;
        z-index: 2;
    }

    .nm-nav-menu-trigger {
        flex: 0 0 44px;
    }
}
</style>

<div class="nm-nav--compact">
    <x-webkernel-platform::dropdown
        placement="bottom-end"
        width="sm"
        offset="4"
        maxHeight="min(60dvh, 24rem)"
        shift
        teleport
    >
        <x-slot name="trigger">
            <button
                type="button"
                class="nm-nav-menu-trigger"
                aria-label="Open site menu"
                title="Open site menu"
            >
                <svg
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                    focusable="false"
                >
                    <path d="M3 6h18M3 12h18M3 18h18" />
                </svg>
            </button>
        </x-slot>

        <nav aria-label="Main navigation">
            <x-webkernel-platform::dropdown.list>
                @foreach ($mobile_nav_items as $item)
                    @if (($item['type'] ?? null) === 'currency')
                        @if (count($currency_options) > 1)
                            <x-webkernel-platform::dropdown.list.item
                                tag="div"
                                class="nm-dropdown-currency-item"
                            >
                                <x-studio::currency-switcher
                                    :currency="$currency"
                                    :currency-options="$ordered_currency_options"
                                    :default-currency="$default_currency"
                                    :show-symbol="($currency_config['show_symbol'] ?? false) === true"
                                    :site-prefix="$site_prefix"
                                    :current-path="$request_path"
                                    trigger-label="Currency"
                                    trigger-icon="coins"
                                    trigger-role="menuitem"
                                    trigger-tabindex="-1"
                                />
                            </x-webkernel-platform::dropdown.list.item>
                        @endif
                    @else
                        <x-webkernel-platform::dropdown.list.item
                            tag="a"
                            :href="$item['href']"
                            :aria-current="$request_path === $item['href'] ? 'page' : null"
                            :data-shortlist-link="$item['key'] === 'shortlist' ? true : null"
                        >
                            <span class="nm-nav-item-row">
                                <x-webkernel::icon
                                    :icon="$item['icon']"
                                    set="lucide"
                                    width="16"
                                    height="16"
                                    aria-hidden="true"
                                />
                                <span class="nm-nav-item-label">
                                    {{ $item['mobile_label'] }}
                                </span>
                            </span>

                            @if ($item['key'] === 'shortlist')
                                <x-slot name="badge">
                                    @if ($shortlist_count > 0)
                                        <span data-shortlist-badge>
                                            <x-webkernel-platform::badge
                                                color="gray"
                                                size="sm"
                                            >
                                                <span data-shortlist-count>
                                                    {{ $shortlist_count }}
                                                </span>
                                            </x-webkernel-platform::badge>
                                        </span>
                                    @else
                                        <template data-shortlist-badge-template>
                                            <span data-shortlist-badge>
                                                <x-webkernel-platform::badge
                                                    color="gray"
                                                    size="sm"
                                                >
                                                    <span data-shortlist-count>0</span>
                                                </x-webkernel-platform::badge>
                                            </span>
                                        </template>
                                    @endif
                                </x-slot>
                            @endif
                        </x-webkernel-platform::dropdown.list.item>
                    @endif
                @endforeach
            </x-webkernel-platform::dropdown.list>
        </nav>
    </x-webkernel-platform::dropdown>
</div>
