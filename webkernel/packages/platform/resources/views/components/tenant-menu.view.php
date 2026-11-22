@props([
    'teleport' => false,
])

@php
    use Webkernel\Platform\Actions\Action;
    use Webkernel\Platform\Models\Contracts\HasCurrentTenantLabel;
    use Webkernel\Platform\Support\Facades\PlatformView;
    use Webkernel\Platform\Support\Icons\Heroicon;
    use Webkernel\Platform\Support\View\ComponentAttributeBag;
    use Webkernel\Platform\View\PanelsIconAlias;
    use Webkernel\Platform\View\PanelsRenderHook;
    use Webkernel\View\Htmlable;
    use Webkernel\Utilities\Arr;

    $currentTenant = platform()->getTenant();
    $currentTenantName = platform()->getTenantName($currentTenant);

    $items = $this->getTenantMenuItems();

    $tenants = $this->getSwitchableTenants();
    $canSwitchTenants = filled($tenants);

    $isSearchable = $canSwitchTenants && (platform()->isTenantMenuSearchable() ?? (count($tenants) >= 10));

    $itemsBeforeAndAfterTenantSwitcher = collect($items)
        ->groupBy(fn (Action $item): bool => $canSwitchTenants && ($item->getSort() < 0), preserveKeys: true)
        ->all();
    $itemsBeforeTenantSwitcher = $itemsBeforeAndAfterTenantSwitcher[true] ?? collect();
    $itemsAfterTenantSwitcher = $itemsBeforeAndAfterTenantSwitcher[false] ?? collect();

    $multiGroupAfterSwitcher = $this->hasMultipleTenantMenuItemGroups();
    $afterSwitcherItemGroups = $multiGroupAfterSwitcher ? $this->getTenantMenuItemGroupsAfterSwitcher() : [];

    $isSidebarCollapsibleOnDesktop = platform()->isSidebarCollapsibleOnDesktop();

    if ($isSearchable) {
        foreach ($items as $item) {
            $item->extraAttributes([
                'role' => ($item->getUrl() && (! $item->shouldPostToUrl())) ? 'link' : 'button',
                'tabindex' => '0',
            ], merge: true);
        }
    }
@endphp

{{ PlatformView::renderHook(PanelsRenderHook::TENANT_MENU_BEFORE) }}

<x-webkernel-platform::dropdown
    :menu="! $isSearchable"
    placement="bottom-start"
    size
    :teleport="$teleport"
    :attributes="
        \Webkernel\Platform\Support\prepare_inherited_attributes($attributes)
            ->class(['fi-tenant-menu'])
    "
>
    <x-slot name="trigger">
        <button
            @if ($isSidebarCollapsibleOnDesktop)
                x-data="{ tooltip: false }"
                x-effect="
                    tooltip = $store.sidebar.isOpen
                        ? false
                        : {
                              content: @js(($currentTenantName instanceof Htmlable) ? $currentTenantName->toHtml() : $currentTenantName),
                              allowHTML: @js($currentTenantName instanceof Htmlable),
                              placement: document.dir === 'rtl' ? 'left' : 'right',
                              theme: $store.theme,
                          }
                "
                x-tooltip="tooltip"
            @endif
            type="button"
            class="fi-tenant-menu-trigger"
        >
            <x-webkernel-platform::avatar.tenant
                :tenant="$currentTenant"
                loading="lazy"
            />

            <span
                @if ($isSidebarCollapsibleOnDesktop)
                    x-show="$store.sidebar.isOpen"
                @endif
                class="fi-tenant-menu-trigger-text"
            >
                @if ($currentTenant instanceof HasCurrentTenantLabel)
                    <span class="fi-tenant-menu-trigger-current-tenant-label">
                        {{ $currentTenant->getCurrentTenantLabel() }}
                    </span>
                @endif

                <span class="fi-tenant-menu-trigger-tenant-name">
                    {{ $currentTenantName }}
                </span>
            </span>

            {{
                \Webkernel\Platform\Support\generate_icon_html(Heroicon::ChevronDown, alias: PanelsIconAlias::TENANT_MENU_TOGGLE_BUTTON, attributes: new ComponentAttributeBag([
                    'x-show' => $isSidebarCollapsibleOnDesktop ? '$store.sidebar.isOpen' : null,
                ]))
            }}
        </button>
    </x-slot>

    @if ($itemsBeforeTenantSwitcher->isNotEmpty())
        <x-webkernel-platform::dropdown.list>
            @foreach ($itemsBeforeTenantSwitcher as $item)
                {{ $item }}
            @endforeach
        </x-webkernel-platform::dropdown.list>
    @endif

    @if ($canSwitchTenants)
        <div x-data="platformTenantMenu">
            <x-webkernel-platform::dropdown.list>
                @if ($isSearchable)
                    <div x-id="['input']">
                        <label x-bind:for="$id('input')" class="fi-sr-only">
                            {{ __('webkernel-platform::layout.tenant_menu.search_field.label') }}
                        </label>

                        <x-webkernel-platform::input
                            x-bind:id="$id('input')"
                            x-model="search"
                            :placeholder="__('webkernel-platform::layout.tenant_menu.search_field.placeholder')"
                            type="search"
                        />
                    </div>
                @endif

                @foreach ($tenants as $tenant)
                    @php
                        $tenantImage = platform()->getTenantAvatarUrl($tenant);
                        $tenantName = platform()->getTenantName($tenant);
                        $tenantUrl = platform()->getUrl($tenant);
                    @endphp

                    <div
                        class="fi-tenant-menu-item"
                        x-show="matchesSearch({{ \Webkernel\View\View::js(($tenantName instanceof Htmlable) ? html_entity_decode(strip_tags($tenantName->toHtml()), ENT_QUOTES | ENT_HTML5, 'UTF-8') : $tenantName) }})"
                    >
                        @if ($isSearchable)
                            <a
                                {{ \Webkernel\Platform\Support\generate_href_html($tenantUrl) }}
                                class="fi-dropdown-list-item"
                            >
                                @if ($tenantImage)
                                    <div
                                        class="fi-dropdown-list-item-image"
                                        style="
                                            background-image: url('{{ $tenantImage }}');
                                        "
                                    ></div>
                                @endif

                                <span class="fi-dropdown-list-item-label">
                                    {{ $tenantName }}
                                </span>
                            </a>
                        @else
                            <x-webkernel-platform::dropdown.list.item
                                :href="$tenantUrl"
                                :image="$tenantImage"
                                tag="a"
                            >
                                {{ $tenantName }}
                            </x-webkernel-platform::dropdown.list.item>
                        @endif
                    </div>
                @endforeach
            </x-webkernel-platform::dropdown.list>
        </div>
    @endif

    @if ($multiGroupAfterSwitcher && $afterSwitcherItemGroups !== [])
        @foreach ($afterSwitcherItemGroups as $afterSwitcherGroup)
            <x-webkernel-platform::dropdown.list>
                @foreach ($afterSwitcherGroup as $item)
                    {{ $item }}
                @endforeach
            </x-webkernel-platform::dropdown.list>
        @endforeach
    @elseif ($itemsAfterTenantSwitcher->isNotEmpty())
        <x-webkernel-platform::dropdown.list>
            @foreach ($itemsAfterTenantSwitcher as $item)
                {{ $item }}
            @endforeach
        </x-webkernel-platform::dropdown.list>
    @endif
</x-webkernel-platform::dropdown>

{{ PlatformView::renderHook(PanelsRenderHook::TENANT_MENU_AFTER) }}
