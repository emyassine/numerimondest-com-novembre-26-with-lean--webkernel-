@props([
    'fullHeight' => false,
    'livewire',
])

@php
    use Webkernel\Platform\Pages\Enums\SubNavigationPosition;
    use Webkernel\Platform\Support\Facades\PlatformView;
    use Webkernel\Platform\Tables\Contracts\HasTable;
    use Webkernel\Platform\View\PanelsRenderHook;
    use Webkernel\View\Htmlable;

    $subNavigation = $livewire->getCachedSubNavigation();
    $subNavigationPosition = $livewire->getSubNavigationPosition();
    $widgetData = $livewire->getWidgetData();
@endphp

<div
    {{
        $attributes->class([
            'fi-page',
            'fi-height-full' => $fullHeight,
            'fi-page-has-sub-navigation' => $subNavigation,
            "fi-page-has-sub-navigation-{$subNavigationPosition->value}" => $subNavigation,
            ...$livewire->getPageClasses(),
        ])
    }}
>
    {{ PlatformView::renderHook(PanelsRenderHook::PAGE_START, scopes: $livewire->getRenderHookScopes()) }}

    <div class="fi-page-header-main-ctn">
        @if ($subNavigation)
            <div
                class="fi-page-main-sub-navigation-mobile-menu-render-hook-ctn"
            >
                {{ PlatformView::renderHook(PanelsRenderHook::PAGE_SUB_NAVIGATION_MOBILE_MENU_BEFORE, scopes: $livewire->getRenderHookScopes()) }}
            </div>

            <x-webkernel-platform::page.sub-navigation.mobile-menu
                :navigation="$subNavigation"
            />

            <div
                class="fi-page-main-sub-navigation-mobile-menu-render-hook-ctn"
            >
                {{ PlatformView::renderHook(PanelsRenderHook::PAGE_SUB_NAVIGATION_MOBILE_MENU_AFTER, scopes: $livewire->getRenderHookScopes()) }}
            </div>
        @endif

        @if ($header = $livewire->getHeader())
            {{ $header }}
        @else
            @php
                $heading = $livewire->getHeading();
                $headerActions = $livewire->getCachedHeaderActions();
                $headerActionsAlignment = $livewire->getHeaderActionsAlignment();
                $breadcrumbs = platform()->hasBreadcrumbs() ? $livewire->getBreadcrumbs() : [];
                $subheading = $livewire->getSubheading();
            @endphp

            @if (filled($headerActions) || $breadcrumbs || filled($heading) || filled($subheading))
                <x-webkernel-platform::header
                    :actions="$headerActions"
                    :actions-alignment="$headerActionsAlignment"
                    :breadcrumbs="$breadcrumbs"
                    :heading="$heading"
                    :subheading="$subheading"
                >
                    @if ($heading instanceof Htmlable)
                        <x-slot name="heading">
                            {{ $heading }}
                        </x-slot>
                    @endif

                    @if ($subheading instanceof Htmlable)
                        <x-slot name="subheading">
                            {{ $subheading }}
                        </x-slot>
                    @endif
                </x-webkernel-platform::header>
            @endif
        @endif

        <div class="fi-page-main">
            @if ($subNavigation)
                @if ($subNavigationPosition === SubNavigationPosition::Start)
                    {{ PlatformView::renderHook(PanelsRenderHook::PAGE_SUB_NAVIGATION_START_BEFORE, scopes: $livewire->getRenderHookScopes()) }}

                    <x-webkernel-platform::page.sub-navigation.sidebar
                        :navigation="$subNavigation"
                    />

                    {{ PlatformView::renderHook(PanelsRenderHook::PAGE_SUB_NAVIGATION_START_AFTER, scopes: $livewire->getRenderHookScopes()) }}
                @endif

                @if ($subNavigationPosition === SubNavigationPosition::Top)
                    {{ PlatformView::renderHook(PanelsRenderHook::PAGE_SUB_NAVIGATION_TOP_BEFORE, scopes: $livewire->getRenderHookScopes()) }}

                    <x-webkernel-platform::page.sub-navigation.tabs
                        :navigation="$subNavigation"
                    />

                    {{ PlatformView::renderHook(PanelsRenderHook::PAGE_SUB_NAVIGATION_TOP_AFTER, scopes: $livewire->getRenderHookScopes()) }}
                @endif
            @endif

            <div class="fi-page-content">
                {{ PlatformView::renderHook(PanelsRenderHook::PAGE_HEADER_WIDGETS_BEFORE, scopes: $livewire->getRenderHookScopes()) }}

                {{ $livewire->headerWidgets }}

                {{ PlatformView::renderHook(PanelsRenderHook::PAGE_HEADER_WIDGETS_AFTER, scopes: $livewire->getRenderHookScopes()) }}

                {{ $slot }}

                {{ PlatformView::renderHook(PanelsRenderHook::PAGE_FOOTER_WIDGETS_BEFORE, scopes: $livewire->getRenderHookScopes()) }}

                {{ $livewire->footerWidgets }}

                {{ PlatformView::renderHook(PanelsRenderHook::PAGE_FOOTER_WIDGETS_AFTER, scopes: $livewire->getRenderHookScopes()) }}
            </div>

            @if ($subNavigation && $subNavigationPosition === SubNavigationPosition::End)
                {{ PlatformView::renderHook(PanelsRenderHook::PAGE_SUB_NAVIGATION_END_BEFORE, scopes: $livewire->getRenderHookScopes()) }}

                <x-webkernel-platform::page.sub-navigation.sidebar
                    :navigation="$subNavigation"
                />

                {{ PlatformView::renderHook(PanelsRenderHook::PAGE_SUB_NAVIGATION_END_AFTER, scopes: $livewire->getRenderHookScopes()) }}
            @endif
        </div>

        @if ($footer = $livewire->getFooter())
            {{ $footer }}
        @endif
    </div>

    @if (! ($livewire instanceof HasTable))
        <x-webkernel-actions::modals />
    @endif

    {{ PlatformView::renderHook(PanelsRenderHook::PAGE_END, scopes: $livewire->getRenderHookScopes()) }}

    @if (method_exists($livewire, 'hasUnsavedDataChangesAlert') && $livewire->hasUnsavedDataChangesAlert())
        @if (PlatformView::hasSpaMode())
            @script
                <script>
                    setUpPlatformSpaModeUnsavedDataChangesAlert({
                        body: @js(__('webkernel-platform::unsaved-changes-alert.body')),
                        resolveLivewireComponentUsing: () => @this,
                        $wire,
                    })
                </script>
            @endscript
        @else
            @script
                <script>
                    setUpPlatformUnsavedDataChangesAlert({ $wire })
                </script>
            @endscript
        @endif
    @endif

    @if (! config('app.debug'))
        @script
            <script>
                window.platformErrorNotifications = @js($livewire->hasErrorNotifications() ? $livewire->getErrorNotifications() : null)
            </script>
        @endscript
    @endif

    <x-webkernel-platform::unsaved-action-changes-alert />
</div>
