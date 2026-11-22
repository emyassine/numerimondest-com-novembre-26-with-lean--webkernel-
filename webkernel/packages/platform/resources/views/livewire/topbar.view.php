@php
    use Webkernel\Platform\Enums\DatabaseNotificationsPosition;
    use Webkernel\Platform\Enums\GlobalSearchPosition;
    use Webkernel\Platform\Enums\UserMenuPosition;
    use Webkernel\Platform\Livewire\GlobalSearch;
    use Webkernel\Platform\Support\Facades\PlatformView;
    use Webkernel\Platform\Support\Icons\Heroicon;
    use Webkernel\Platform\View\PanelsIconAlias;
    use Webkernel\Platform\View\PanelsRenderHook;
@endphp

<div
    x-data="platformSidebarBackground"
    x-bind:inert="$store.sidebar.isOpenMobile && ! hasOpenModal"
    class="fi-topbar-ctn"
>
    @php
        $isRtl = __('webkernel-platform::layout.direction') === 'rtl';
        $isSidebarCollapsibleOnDesktop = platform()->isSidebarCollapsibleOnDesktop();
        $isSidebarFullyCollapsibleOnDesktop = platform()->isSidebarFullyCollapsibleOnDesktop();
        $hasTopNavigation = platform()->hasTopNavigation();
        $hasNavigation = platform()->hasNavigation();
        $hasTenancy = platform()->hasTenancy();
    @endphp

    <nav
        aria-label="{{ __('webkernel-platform::layout.topbar.label') }}"
        class="fi-topbar"
    >
        {{ PlatformView::renderHook(PanelsRenderHook::TOPBAR_START) }}

        @if ($hasNavigation)
            <x-webkernel-platform::icon-button
                color="gray"
                :icon="Heroicon::OutlinedBars3"
                :icon-alias="PanelsIconAlias::TOPBAR_OPEN_SIDEBAR_BUTTON"
                icon-size="lg"
                :label="__('webkernel-platform::layout.actions.sidebar.expand.label')"
                x-cloak
                x-data="{}"
                aria-controls="fi-main-sidebar"
                x-bind:aria-expanded="$store.sidebar.isOpen"
                x-on:click="$store.sidebar.open($event.currentTarget)"
                x-show="! $store.sidebar.isOpen"
                class="fi-topbar-open-sidebar-btn"
            />

            <x-webkernel-platform::icon-button
                color="gray"
                :icon="Heroicon::OutlinedXMark"
                :icon-alias="PanelsIconAlias::TOPBAR_CLOSE_SIDEBAR_BUTTON"
                icon-size="lg"
                :label="__('webkernel-platform::layout.actions.sidebar.collapse.label')"
                x-cloak
                x-data="{}"
                aria-controls="fi-main-sidebar"
                x-bind:aria-expanded="$store.sidebar.isOpen"
                x-on:click="$store.sidebar.close()"
                x-show="$store.sidebar.isOpen"
                class="fi-topbar-close-sidebar-btn"
            />
        @endif

        <div class="fi-topbar-start">
            @if ($isSidebarCollapsibleOnDesktop || $isSidebarFullyCollapsibleOnDesktop)
                <div
                    x-show="$store.sidebar.isOpen || @js($isSidebarCollapsibleOnDesktop)"
                    class="fi-topbar-collapse-sidebar-btn-ctn"
                >
                    @if ($isSidebarCollapsibleOnDesktop)
                        <x-webkernel-platform::icon-button
                            color="gray"
                            :icon="$isRtl ? Heroicon::OutlinedChevronLeft : Heroicon::OutlinedChevronRight"
                            {{-- @deprecated Use `PanelsIconAlias::SIDEBAR_EXPAND_BUTTON_RTL` instead of `PanelsIconAlias::SIDEBAR_EXPAND_BUTTON` for RTL. --}}
                            :icon-alias="
                                $isRtl
                                ? [
                                    PanelsIconAlias::SIDEBAR_EXPAND_BUTTON_RTL,
                                    PanelsIconAlias::SIDEBAR_EXPAND_BUTTON,
                                ]
                                : PanelsIconAlias::SIDEBAR_EXPAND_BUTTON
                            "
                            icon-size="lg"
                            :label="__('webkernel-platform::layout.actions.sidebar.expand.label')"
                            x-cloak
                            x-data="{}"
                            aria-controls="fi-main-sidebar"
                            x-bind:aria-expanded="$store.sidebar.isOpen"
                            x-on:click="$store.sidebar.open()"
                            x-show="! $store.sidebar.isOpen"
                            class="fi-topbar-open-collapse-sidebar-btn"
                        />
                    @endif

                    @if ($isSidebarCollapsibleOnDesktop || $isSidebarFullyCollapsibleOnDesktop)
                        <x-webkernel-platform::icon-button
                            color="gray"
                            :icon="$isRtl ? Heroicon::OutlinedChevronRight : Heroicon::OutlinedChevronLeft"
                            {{-- @deprecated Use `PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON_RTL` instead of `PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON` for RTL. --}}
                            :icon-alias="
                                $isRtl
                                ? [
                                    PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON_RTL,
                                    PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON,
                                ]
                                : PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON
                            "
                            icon-size="lg"
                            :label="__('webkernel-platform::layout.actions.sidebar.collapse.label')"
                            x-cloak
                            x-data="{}"
                            aria-controls="fi-main-sidebar"
                            x-bind:aria-expanded="$store.sidebar.isOpen"
                            x-on:click="$store.sidebar.close()"
                            x-show="$store.sidebar.isOpen"
                            class="fi-topbar-close-collapse-sidebar-btn"
                        />
                    @endif
                </div>
            @endif

            {{ PlatformView::renderHook(PanelsRenderHook::TOPBAR_LOGO_BEFORE) }}

            @if ($homeUrl = platform()->getHomeUrl())
                <a {{ \Webkernel\Platform\Support\generate_href_html($homeUrl) }}>
                    <x-webkernel-platform::logo />
                </a>
            @else
                <x-webkernel-platform::logo />
            @endif

            {{ PlatformView::renderHook(PanelsRenderHook::TOPBAR_LOGO_AFTER) }}
        </div>

        @if ($hasTopNavigation || (! $hasNavigation))
            @if ($hasTenancy && platform()->hasTenantMenu())
                <x-webkernel-platform::tenant-menu teleport />
            @endif

            @if ($hasNavigation)
                @php
                    $navigation = platform()->getNavigation();
                @endphp

                <ul class="fi-topbar-nav-groups">
                    @foreach ($navigation as $group)
                        @php
                            $groupLabel = $group->getLabel();
                            $groupExtraTopbarAttributeBag = $group->getExtraTopbarAttributeBag();
                            $isGroupActive = $group->isActive();
                            $groupIcon = $group->getIcon();
                        @endphp

                        @if ($groupLabel)
                            <x-webkernel-platform::dropdown
                                placement="bottom-start"
                                teleport
                                :attributes="\Webkernel\Platform\Support\prepare_inherited_attributes($groupExtraTopbarAttributeBag)"
                            >
                                <x-slot name="trigger">
                                    <x-webkernel-platform::topbar.item
                                        :active="$isGroupActive"
                                        :icon="$groupIcon"
                                    >
                                        {{ $groupLabel }}
                                    </x-webkernel-platform::topbar.item>
                                </x-slot>

                                @php
                                    $lists = [];

                                    foreach ($group->getItems() as $item) {
                                        if ($childItems = $item->getChildItems()) {
                                            $lists[] = [
                                                $item,
                                                ...$childItems,
                                            ];
                                            $lists[] = [];

                                            continue;
                                        }

                                        if (empty($lists)) {
                                            $lists[] = [$item];

                                            continue;
                                        }

                                        $lists[count($lists) - 1][] = $item;
                                    }

                                    if (empty($lists[count($lists) - 1])) {
                                        array_pop($lists);
                                    }
                                @endphp

                                @foreach ($lists as $list)
                                    <x-webkernel-platform::dropdown.list>
                                        @foreach ($list as $item)
                                            @php
                                                $isItemActive = $item->isActive();
                                                $itemBadge = $item->getBadge();
                                                $itemBadgeColor = $item->getBadgeColor($itemBadge);
                                                $itemBadgeTooltip = $item->getBadgeTooltip($itemBadge);
                                                $itemUrl = $item->getUrl();
                                                $itemIcon = $isItemActive ? ($item->getActiveIcon() ?? $item->getIcon()) : $item->getIcon();
                                                $shouldItemOpenUrlInNewTab = $item->shouldOpenUrlInNewTab();
                                                $itemExtraAttributes = $item->getExtraAttributeBag();
                                            @endphp

                                            <x-webkernel-platform::dropdown.list.item
                                                :badge="$itemBadge"
                                                :badge-color="$itemBadgeColor"
                                                :badge-tooltip="$itemBadgeTooltip"
                                                :color="$isItemActive ? 'primary' : 'gray'"
                                                :href="$itemUrl"
                                                :icon="$itemIcon"
                                                tag="a"
                                                :target="$shouldItemOpenUrlInNewTab ? '_blank' : null"
                                                :aria-current="$isItemActive ? 'page' : null"
                                                :attributes="\Webkernel\Platform\Support\prepare_inherited_attributes($itemExtraAttributes)"
                                            >
                                                {{ $item->getLabel() }}
                                            </x-webkernel-platform::dropdown.list.item>
                                        @endforeach
                                    </x-webkernel-platform::dropdown.list>
                                @endforeach
                            </x-webkernel-platform::dropdown>
                        @else
                            @foreach ($group->getItems() as $item)
                                @php
                                    $isItemActive = $item->isActive();
                                    $itemActiveIcon = $item->getActiveIcon();
                                    $itemBadge = $item->getBadge();
                                    $itemBadgeColor = $item->getBadgeColor($itemBadge);
                                    $itemBadgeTooltip = $item->getBadgeTooltip($itemBadge);
                                    $itemIcon = $item->getIcon();
                                    $shouldItemOpenUrlInNewTab = $item->shouldOpenUrlInNewTab();
                                    $itemUrl = $item->getUrl();
                                    $itemExtraAttributes = $item->getExtraAttributeBag();
                                @endphp

                                <x-webkernel-platform::topbar.item
                                    :active="$isItemActive"
                                    :active-icon="$itemActiveIcon"
                                    :badge="$itemBadge"
                                    :badge-color="$itemBadgeColor"
                                    :badge-tooltip="$itemBadgeTooltip"
                                    :icon="$itemIcon"
                                    :should-open-url-in-new-tab="$shouldItemOpenUrlInNewTab"
                                    :url="$itemUrl"
                                    :attributes="\Webkernel\Platform\Support\prepare_inherited_attributes($itemExtraAttributes)"
                                >
                                    {{ $item->getLabel() }}
                                </x-webkernel-platform::topbar.item>
                            @endforeach
                        @endif
                    @endforeach
                </ul>
            @endif
        @endif

        <div
            @if ($hasTenancy)
                x-persist="topbar.end.panel-{{ platform()->getId() }}.tenant-{{ platform()->getTenant()?->getKey() }}"
            @else
                x-persist="topbar.end.panel-{{ platform()->getId() }}"
            @endif
            class="fi-topbar-end"
        >
            {{ PlatformView::renderHook(PanelsRenderHook::GLOBAL_SEARCH_BEFORE) }}

            @if (platform()->isGlobalSearchEnabled() && platform()->getGlobalSearchPosition() === GlobalSearchPosition::Topbar)
                @liveview(GlobalSearch::class)
            @endif

            {{ PlatformView::renderHook(PanelsRenderHook::GLOBAL_SEARCH_AFTER) }}

            @if (platform()->auth()->check())
                @if (platform()->hasDatabaseNotifications() && platform()->getDatabaseNotificationsPosition() === DatabaseNotificationsPosition::Topbar)
                    @liveview(platform()->getDatabaseNotificationsLivewireComponent(), [
                        'lazy' => platform()->hasLazyLoadedDatabaseNotifications(),
                    ])
                @endif

                @if (platform()->hasUserMenu() && platform()->getUserMenuPosition() === UserMenuPosition::Topbar)
                    <x-webkernel-platform::user-menu />
                @endif
            @endif
        </div>

        {{ PlatformView::renderHook(PanelsRenderHook::TOPBAR_END) }}
    </nav>

    <x-webkernel-actions::modals />
</div>
