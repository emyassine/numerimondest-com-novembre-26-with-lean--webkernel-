@props([
    'after' => null,
    'heading' => null,
    'subheading' => null,
])

@php
    use Webkernel\Platform\Livewire\SimpleUserMenu;
    use Webkernel\Platform\Support\Enums\Width;
    use Webkernel\Platform\Support\Facades\PlatformView;
    use Webkernel\Platform\View\PanelsRenderHook;

    $livewire ??= null;

    $renderHookScopes = $livewire?->getRenderHookScopes();
    $maxContentWidth ??= (platform()->getSimplePageMaxContentWidth() ?? Width::Large);

    if (is_string($maxContentWidth)) {
        $maxContentWidth = Width::tryFrom($maxContentWidth) ?? $maxContentWidth;
    }
@endphp

<x-webkernel-platform::layout.base :livewire="$livewire">
    <div class="fi-simple-layout">
        @if (($hasTopbar ?? true) && platform()->auth()->check())
            <a href="#fi-main-content" class="fi-skip-link fi-sr-only">
                {{ __('webkernel-platform::layout.skip_to_content.label') }}
            </a>
        @endif

        {{ PlatformView::renderHook(PanelsRenderHook::SIMPLE_LAYOUT_START, scopes: $renderHookScopes) }}

        @if (($hasTopbar ?? true) && platform()->auth()->check())
            <div class="fi-simple-layout-header">
                @if (platform()->hasDatabaseNotifications())
                    @liveview(platform()->getDatabaseNotificationsLivewireComponent(), [
                        'lazy' => platform()->hasLazyLoadedDatabaseNotifications(),
                        'position' => \Webkernel\Platform\Enums\DatabaseNotificationsPosition::Topbar,
                    ])
                @endif

                @if (platform()->hasUserMenu())
                    @liveview(SimpleUserMenu::class)
                @endif
            </div>
        @endif

        <div class="fi-simple-main-ctn">
            <main
                id="fi-main-content"
                tabindex="-1"
                @class([
                    'fi-simple-main',
                    ($maxContentWidth instanceof Width) ? "fi-width-{$maxContentWidth->value}" : $maxContentWidth,
                ])
            >
                {{ $slot }}
            </main>
        </div>

        {{ PlatformView::renderHook(PanelsRenderHook::FOOTER, scopes: $renderHookScopes) }}

        {{ PlatformView::renderHook(PanelsRenderHook::SIMPLE_LAYOUT_END, scopes: $renderHookScopes) }}
    </div>
</x-webkernel-platform::layout.base>
