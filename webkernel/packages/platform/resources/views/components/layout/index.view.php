@php
    use Webkernel\Platform\Support\Enums\Width;
    use Webkernel\Platform\Support\Facades\PlatformView;
    use Webkernel\Platform\Support\Icons\Heroicon;
    use Webkernel\Platform\View\PanelsIconAlias;
    use Webkernel\Platform\View\PanelsRenderHook;

    $livewire ??= null;

    $hasTopbar = platform()->hasTopbar();
    $isSidebarCollapsibleOnDesktop = platform()->isSidebarCollapsibleOnDesktop();
    $isSidebarFullyCollapsibleOnDesktop = platform()->isSidebarFullyCollapsibleOnDesktop();
    $hasTopNavigation = platform()->hasTopNavigation();
    $hasNavigation = platform()->hasNavigation();
    $renderHookScopes = $livewire?->getRenderHookScopes();
    $maxContentWidth ??= (platform()->getMaxContentWidth() ?? Width::SevenExtraLarge);

    if (is_string($maxContentWidth)) {
        $maxContentWidth = Width::tryFrom($maxContentWidth) ?? $maxContentWidth;
    }
@endphp

<x-webkernel-platform::layout.base
    :livewire="$livewire"
    :class="implode(' ', array_keys(array_filter([
        'fi-body-has-navigation' => $hasNavigation,
        'fi-body-has-sidebar-collapsible-on-desktop' => $isSidebarCollapsibleOnDesktop,
        'fi-body-has-sidebar-fully-collapsible-on-desktop' => $isSidebarFullyCollapsibleOnDesktop,
        'fi-body-has-topbar' => $hasTopbar,
        'fi-body-has-top-navigation' => $hasTopNavigation,
    ])))"
>
    <a
        x-data="{}"
        x-bind:inert="$store.sidebar.isOpenMobile"
        href="#fi-main-content"
        class="fi-skip-link fi-sr-only"
    >
        {{ __('webkernel-platform::layout.skip_to_content.label') }}
    </a>

    @if ($hasTopbar)
        {{ PlatformView::renderHook(PanelsRenderHook::TOPBAR_BEFORE, scopes: $renderHookScopes) }}

        @liveview(platform()->getTopbarLivewireComponent())

        {{ PlatformView::renderHook(PanelsRenderHook::TOPBAR_AFTER, scopes: $renderHookScopes) }}
    @elseif ($hasNavigation)
        <div
            x-data="{}"
            x-bind:inert="$store.sidebar.isOpenMobile"
            @if ($isSidebarFullyCollapsibleOnDesktop)
                x-bind:class="{ 'lg:fi-hidden': $store.sidebar.isOpen }"
            @endif
            @class([
                'fi-layout-sidebar-toggle-btn-ctn',
                'lg:fi-hidden' => ! $isSidebarFullyCollapsibleOnDesktop,
            ])
        >
            <x-webkernel-platform::icon-button
                color="gray"
                :icon="Heroicon::OutlinedBars3"
                :icon-alias="PanelsIconAlias::SIDEBAR_EXPAND_BUTTON"
                icon-size="lg"
                :label="__('webkernel-platform::layout.actions.sidebar.expand.label')"
                x-cloak
                x-data="{}"
                aria-controls="fi-main-sidebar"
                x-bind:aria-expanded="$store.sidebar.isOpen"
                x-on:click="$store.sidebar.open($event.currentTarget)"
                class="fi-layout-sidebar-toggle-btn"
            />
        </div>
    @endif

    <div class="fi-layout">
        {{ PlatformView::renderHook(PanelsRenderHook::LAYOUT_START, scopes: $renderHookScopes) }}

        @if ($hasNavigation)
            <div
                x-cloak
                x-data="{}"
                x-on:click="$store.sidebar.close()"
                x-show="$store.sidebar.isOpen"
                x-transition.opacity.300ms
                class="fi-sidebar-close-overlay"
            ></div>

            @liveview(platform()->getSidebarLivewireComponent())
        @endif

        <div
            x-data="platformSidebarBackground"
            x-bind:inert="$store.sidebar.isOpenMobile && ! hasOpenModal"
            @if ($isSidebarCollapsibleOnDesktop)
                x-bind:class="{
                    'fi-main-ctn-sidebar-open': $store.sidebar.isOpen,
                }"
                x-bind:style="'display: flex; opacity:1;'"
                {{-- Mimics `x-cloak`, as using `x-cloak` causes visual issues with chart widgets --}}
            @elseif ($isSidebarFullyCollapsibleOnDesktop)
                x-bind:class="{
                    'fi-main-ctn-sidebar-open': $store.sidebar.isOpen,
                }"
                x-bind:style="'display: flex; opacity:1;'"
                {{-- Mimics `x-cloak`, as using `x-cloak` causes visual issues with chart widgets --}}
            @elseif (! ($isSidebarCollapsibleOnDesktop || $isSidebarFullyCollapsibleOnDesktop || $hasTopNavigation || (! $hasNavigation)))
                x-bind:style="'display: flex; opacity:1;'" {{-- Mimics `x-cloak`, as using `x-cloak` causes visual issues with chart widgets --}}
            @endif
            class="fi-main-ctn"
        >
            {{ PlatformView::renderHook(PanelsRenderHook::CONTENT_BEFORE, scopes: $renderHookScopes) }}

            <main
                id="fi-main-content"
                tabindex="-1"
                @class([
                    'fi-main',
                    ($maxContentWidth instanceof Width) ? "fi-width-{$maxContentWidth->value}" : $maxContentWidth,
                ])
            >
                {{ PlatformView::renderHook(PanelsRenderHook::CONTENT_START, scopes: $renderHookScopes) }}

                {{ $slot }}

                {{ PlatformView::renderHook(PanelsRenderHook::CONTENT_END, scopes: $renderHookScopes) }}
            </main>

            {{ PlatformView::renderHook(PanelsRenderHook::CONTENT_AFTER, scopes: $renderHookScopes) }}

            {{ PlatformView::renderHook(PanelsRenderHook::FOOTER, scopes: $renderHookScopes) }}
        </div>

        {{ PlatformView::renderHook(PanelsRenderHook::LAYOUT_END, scopes: $renderHookScopes) }}
    </div>
</x-webkernel-platform::layout.base>
