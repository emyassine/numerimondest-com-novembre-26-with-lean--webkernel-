<div>
    @php
        use Webkernel\Platform\Enums\GlobalSearchPosition;

        $navigation = platform()->getNavigation();
        $isRtl = __('webkernel-platform::layout.direction') === 'rtl';
        $isSidebarCollapsibleOnDesktop = platform()->isSidebarCollapsibleOnDesktop();
        $isSidebarFullyCollapsibleOnDesktop = platform()->isSidebarFullyCollapsibleOnDesktop();
        $hasNavigation = platform()->hasNavigation();
        $hasTopbar = platform()->hasTopbar();
        $hasTenantMenu = platform()->hasTenancy() && platform()->hasTenantMenu();
        $hasGlobalSearchInSidebar = platform()->isGlobalSearchEnabled() && platform()->getGlobalSearchPosition() === GlobalSearchPosition::Sidebar;

        $cspNonce = $csp_nonce ?? null;
    @endphp

    {{-- format-ignore-start --}}
    <div
        x-data="platformSidebar({
            hasTopNavigation: @js(platform()->hasTopNavigation()),
            isSidebarCollapsibleOnDesktop: @js($isSidebarCollapsibleOnDesktop),
            isSidebarFullyCollapsibleOnDesktop: @js($isSidebarFullyCollapsibleOnDesktop),
        })"
        @if ($isSidebarCollapsibleOnDesktop || $isSidebarFullyCollapsibleOnDesktop)
            x-cloak
        @else
            x-cloak="-lg"
        @endif
        x-bind:class="{ 'fi-sidebar-open': $store.sidebar.isOpen }"
        x-bind:inert="isHidden"
        x-effect="focusInitialNavigation()"
        x-on:keydown.tab="handleTab($event)"
        x-on:keydown.escape.document="handleEscape($event)"
        id="fi-main-sidebar"
        class="fi-sidebar fi-main-sidebar"
    >
        {{ \Webkernel\Platform\Support\Facades\PlatformView::renderHook(\Webkernel\Platform\View\PanelsRenderHook::SIDEBAR_START) }}

        <div class="fi-sidebar-header-ctn">
            <header
                class="fi-sidebar-header"
            >
                @if ((! $hasTopbar) && $isSidebarCollapsibleOnDesktop)
                    <x-webkernel-platform::icon-button
                        color="gray"
                        :icon="$isRtl ? \Webkernel\Platform\Support\Icons\Heroicon::OutlinedChevronLeft : \Webkernel\Platform\Support\Icons\Heroicon::OutlinedChevronRight"
                        {{-- @deprecated Use `PanelsIconAlias::SIDEBAR_EXPAND_BUTTON_RTL` instead of `PanelsIconAlias::SIDEBAR_EXPAND_BUTTON` for RTL. --}}
                        :icon-alias="
                            $isRtl
                            ? [
                                \Webkernel\Platform\View\PanelsIconAlias::SIDEBAR_EXPAND_BUTTON_RTL,
                                \Webkernel\Platform\View\PanelsIconAlias::SIDEBAR_EXPAND_BUTTON,
                            ]
                            : \Webkernel\Platform\View\PanelsIconAlias::SIDEBAR_EXPAND_BUTTON
                        "
                        icon-size="lg"
                        :label="__('webkernel-platform::layout.actions.sidebar.expand.label')"
                        x-cloak
                        x-data="{}"
                        aria-controls="fi-main-sidebar"
                        x-bind:aria-expanded="$store.sidebar.isOpen"
                        x-on:click="$store.sidebar.open()"
                        x-show="! $store.sidebar.isOpen"
                        class="fi-sidebar-open-collapse-sidebar-btn"
                    />
                @endif

                @if ((! $hasTopbar) && ($isSidebarCollapsibleOnDesktop || $isSidebarFullyCollapsibleOnDesktop))
                    <x-webkernel-platform::icon-button
                        color="gray"
                        :icon="$isRtl ? \Webkernel\Platform\Support\Icons\Heroicon::OutlinedChevronRight : \Webkernel\Platform\Support\Icons\Heroicon::OutlinedChevronLeft"
                        {{-- @deprecated Use `PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON_RTL` instead of `PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON` for RTL. --}}
                        :icon-alias="
                            $isRtl
                            ? [
                                \Webkernel\Platform\View\PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON_RTL,
                                \Webkernel\Platform\View\PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON,
                            ]
                            : \Webkernel\Platform\View\PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON
                        "
                        icon-size="lg"
                        :label="__('webkernel-platform::layout.actions.sidebar.collapse.label')"
                        x-cloak
                        x-data="{}"
                        aria-controls="fi-main-sidebar"
                        x-bind:aria-expanded="$store.sidebar.isOpen"
                        x-on:click="$store.sidebar.close()"
                        x-show="$store.sidebar.isOpen"
                        class="fi-sidebar-close-collapse-sidebar-btn"
                    />
                @endif

                {{ \Webkernel\Platform\Support\Facades\PlatformView::renderHook(\Webkernel\Platform\View\PanelsRenderHook::SIDEBAR_LOGO_BEFORE) }}

                <div
                    @if ($isSidebarCollapsibleOnDesktop || $isSidebarFullyCollapsibleOnDesktop)
                        x-show="$store.sidebar.isOpen"
                    @endif
                    class="fi-sidebar-header-logo-ctn"
                >
                    @if ($homeUrl = platform()->getHomeUrl())
                        <a {{ \Webkernel\Platform\Support\generate_href_html($homeUrl) }}>
                            <x-webkernel-platform::logo />
                        </a>
                    @else
                        <x-webkernel-platform::logo />
                    @endif
                </div>

                {{ \Webkernel\Platform\Support\Facades\PlatformView::renderHook(\Webkernel\Platform\View\PanelsRenderHook::SIDEBAR_LOGO_AFTER) }}
            </header>
        </div>

        @if ($hasTenantMenu || $hasGlobalSearchInSidebar)
            <div
                @if ((! $hasTenantMenu) && ($isSidebarCollapsibleOnDesktop || $isSidebarFullyCollapsibleOnDesktop))
                    x-show="$store.sidebar.isOpen"
                @endif
                class="fi-sidebar-header-controls"
            >
                @if ($hasTenantMenu)
                    <x-webkernel-platform::tenant-menu />
                @endif

                @if ($hasGlobalSearchInSidebar)
                    <div
                        @if ($isSidebarCollapsibleOnDesktop || $isSidebarFullyCollapsibleOnDesktop)
                            x-show="$store.sidebar.isOpen"
                        @endif
                    >
                        @liveview(Webkernel\Platform\Livewire\GlobalSearch::class)
                    </div>
                @endif
            </div>
        @endif

        <nav
            aria-label="{{ __('webkernel-platform::layout.navigation.label') }}"
            class="fi-sidebar-nav"
        >
            {{ \Webkernel\Platform\Support\Facades\PlatformView::renderHook(\Webkernel\Platform\View\PanelsRenderHook::SIDEBAR_NAV_START) }}

            <ul class="fi-sidebar-nav-groups">
                @foreach ($navigation as $group)
                    @php
                        $isGroupActive = $group->isActive();
                        $isGroupCollapsible = $group->isCollapsible();
                        $groupIcon = $group->getIcon();
                        $groupItems = $group->getItems();
                        $groupLabel = $group->getLabel();
                        $groupExtraSidebarAttributeBag = $group->getExtraSidebarAttributeBag();
                    @endphp

                    <x-webkernel-platform::sidebar.group
                        :active="$isGroupActive"
                        :collapsible="$isGroupCollapsible"
                        :icon="$groupIcon"
                        :items="$groupItems"
                        :label="$groupLabel"
                        :attributes="\Webkernel\Platform\Support\prepare_inherited_attributes($groupExtraSidebarAttributeBag)"
                    />
                @endforeach
            </ul>

            <script @if (filled($cspNonce)) nonce="{{ $cspNonce }}" @endif>
                if (! window.Alpine?.store('sidebar')) {
                    let collapsedGroups = JSON.parse(
                        localStorage.getItem('platform:sidebar:collapsed_groups'),
                    )

                    if (collapsedGroups === null || collapsedGroups === 'null') {
                        collapsedGroups = @js(
                            collect($navigation)
                                ->filter(fn (\Webkernel\Platform\Navigation\NavigationGroup $group): bool => $group->isCollapsed())
                                ->map(fn (\Webkernel\Platform\Navigation\NavigationGroup $group): ?string => ($label = $group->getLabel()) instanceof \Webkernel\View\Htmlable ? $label->toHtml() : $label)
                                ->values()
                                ->all()
                        )

                        localStorage.setItem(
                            'platform:sidebar:collapsed_groups',
                            JSON.stringify(collapsedGroups),
                        )
                    }

                    document.currentScript
                        .closest('nav')
                        .querySelectorAll('.fi-sidebar-group')
                        .forEach((group) => {
                            if (! collapsedGroups.includes(group.dataset.groupLabel)) {
                                return
                            }

                            // Hide collapsed groups before Alpine initializes to avoid a flash.
                            group.querySelector('.fi-sidebar-group-items').style.display = 'none'
                            group.classList.add('fi-collapsed')
                        })
                }
            </script>

            {{ \Webkernel\Platform\Support\Facades\PlatformView::renderHook(\Webkernel\Platform\View\PanelsRenderHook::SIDEBAR_NAV_END) }}
        </nav>

        @php
            $isAuthenticated = platform()->auth()->check();
            $hasDatabaseNotificationsInSidebar = platform()->hasDatabaseNotifications() && platform()->getDatabaseNotificationsPosition() === \Webkernel\Platform\Enums\DatabaseNotificationsPosition::Sidebar;
            $hasUserMenuInSidebar = platform()->hasUserMenu() && platform()->getUserMenuPosition() === \Webkernel\Platform\Enums\UserMenuPosition::Sidebar;
            $shouldRenderFooter = $isAuthenticated && ($hasDatabaseNotificationsInSidebar || $hasUserMenuInSidebar);
        @endphp

        @if ($shouldRenderFooter)
            <div class="fi-sidebar-footer">
                @if ($hasDatabaseNotificationsInSidebar)
                    @liveview(platform()->getDatabaseNotificationsLivewireComponent(), [
                        'lazy' => platform()->hasLazyLoadedDatabaseNotifications(),
                    ])
                @endif

                @if ($hasUserMenuInSidebar)
                    <x-webkernel-platform::user-menu />
                @endif
            </div>
        @endif

        {{ \Webkernel\Platform\Support\Facades\PlatformView::renderHook(\Webkernel\Platform\View\PanelsRenderHook::SIDEBAR_FOOTER) }}
    </div>
    {{-- format-ignore-end --}}

    <x-webkernel-actions::modals />
</div>
