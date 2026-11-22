@props([
    'livewire' => null,
])

@php
    use Webkernel\Platform\Livewire\Notifications;
    use Webkernel\Platform\Support\Facades\PlatformView;
    use Webkernel\Platform\View\PanelsRenderHook;

    $renderHookScopes = $livewire?->getRenderHookScopes();

    $cspNonce = $csp_nonce ?? null;
@endphp

<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', \Webkernel\I18n\I18nContext::get_locale()) }}"
    dir="{{ __('webkernel-platform::layout.direction') ?? 'ltr' }}"
    @class([
        'fi',
        'dark' => platform()->hasDarkMode() && platform()->hasDarkModeForced(),
    ])
>
    <head>
        {{ PlatformView::renderHook(PanelsRenderHook::HEAD_START, scopes: $renderHookScopes) }}

        <meta charset="utf-8" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />

        @if ($favicon = platform()->getFavicon())
            <link rel="icon" href="{{ $favicon }}" />
        @endif

        @php
            $title = trim(strip_tags($livewire?->getTitle() ?? ''));
            $brandName = trim(strip_tags(platform()->getBrandName()));
        @endphp

        <title>
            {{ filled($title) ? $title : null }}
            {{ filled($brandName) && filled($title) ? ' - ' : null }}
            {{ filled($brandName) ? $brandName : null }}
        </title>

        {{ PlatformView::renderHook(PanelsRenderHook::STYLES_BEFORE, scopes: $renderHookScopes) }}

        <style @if (filled($cspNonce)) nonce="{{ $cspNonce }}" @endif>
            [x-cloak=''],
            [x-cloak='x-cloak'],
            [x-cloak='1'] {
                display: none !important;
            }

            [x-cloak='inline-flex'] {
                display: inline-flex !important;
            }

            @media (max-width: 1023px) {
                [x-cloak='-lg'] {
                    display: none !important;
                }
            }

            @media (min-width: 1024px) {
                [x-cloak='lg'] {
                    display: none !important;
                }
            }
        </style>

        {{ new \Webkernel\View\HtmlString(\Webkernel\Liveview\Mechanisms\FrontendAssets\FrontendAssets::styles(['nonce' => $cspNonce])) }}

        @platformStyles

        {{ platform()->getTheme()->getHtml() }}
        {{ platform()->getFontPreloadHtml() }}
        {{ platform()->getMonoFontPreloadHtml() }}
        {{ platform()->getSerifFontPreloadHtml() }}
        {{ platform()->getFontHtml() }}
        {{ platform()->getMonoFontHtml() }}
        {{ platform()->getSerifFontHtml() }}

        <style @if (filled($cspNonce)) nonce="{{ $cspNonce }}" @endif>
            :root {
                --font-family: '{!! platform()->getFontFamily() !!}';
                --mono-font-family: '{!! platform()->getMonoFontFamily() !!}';
                --serif-font-family: '{!! platform()->getSerifFontFamily() !!}';
                --sidebar-width: {{ platform()->getSidebarWidth() }};
                --collapsed-sidebar-width: {{ platform()->getCollapsedSidebarWidth() }};
                --default-theme-mode: {{ platform()->getDefaultThemeMode()->value }};
            }

            html.fi {
                --livewire-progress-bar-color: var(--primary-500);
            }
        </style>

        @stack('styles')

        {{ PlatformView::renderHook(PanelsRenderHook::STYLES_AFTER, scopes: $renderHookScopes) }}

        @if (! platform()->hasDarkMode())
            <script @if (filled($cspNonce)) nonce="{{ $cspNonce }}" @endif>
                localStorage.setItem('platform:theme:mode', 'light')
                document.documentElement.classList.remove('dark')
                document.documentElement.dataset.theme = 'light'
            </script>
        @elseif (platform()->hasDarkModeForced())
            <script @if (filled($cspNonce)) nonce="{{ $cspNonce }}" @endif>
                localStorage.setItem('platform:theme:mode', 'dark')
                document.documentElement.classList.add('dark')
                document.documentElement.dataset.theme = 'dark'
            </script>
        @else
            <script @if (filled($cspNonce)) nonce="{{ $cspNonce }}" @endif>
                const loadDarkMode = () => {
                    const theme =
                        localStorage.getItem('platform:theme:mode') ?? @js(platform()->getDefaultThemeMode()->value)

                    const isDark =
                        theme === 'dark' ||
                        (theme === 'system' &&
                            window.matchMedia('(prefers-color-scheme: dark)')
                                .matches)

                    document.documentElement.classList.toggle('dark', isDark)
                    document.documentElement.dataset.theme = isDark ? 'dark' : 'light'
                }

                loadDarkMode()

                document.addEventListener('livewire:navigated', loadDarkMode)
            </script>
        @endif

        {{ PlatformView::renderHook(PanelsRenderHook::HEAD_END, scopes: $renderHookScopes) }}
    </head>

    <body
        {{
            $attributes
                ->merge($livewire?->getExtraBodyAttributes() ?? [], escape: false)
                ->class([
                    'fi-body',
                    'fi-panel-' . platform()->getId(),
                ])
        }}
    >
        {{ PlatformView::renderHook(PanelsRenderHook::BODY_START, scopes: $renderHookScopes) }}

        {{ $slot }}

        @liveview(Notifications::class)

        {{ PlatformView::renderHook(PanelsRenderHook::SCRIPTS_BEFORE, scopes: $renderHookScopes) }}

        @platformScripts(withCore: true)

        {{ new \Webkernel\View\HtmlString(\Webkernel\Liveview\Mechanisms\FrontendAssets\FrontendAssets::scripts()) }}

        @if (platform()->hasBroadcasting() && config('platform.broadcasting.echo'))
            <script
                data-navigate-once
                @if (filled($cspNonce)) nonce="{{ $cspNonce }}" @endif
            >
                window.Echo = new window.EchoFactory(@js(config('platform.broadcasting.echo')))

                window.dispatchEvent(new CustomEvent('EchoLoaded'))
            </script>
        @endif

        @if (platform()->hasDarkMode() && (! platform()->hasDarkModeForced()))
            <script @if (filled($cspNonce)) nonce="{{ $cspNonce }}" @endif>
                loadDarkMode()
            </script>
        @endif

        @stack('scripts')

        {{ PlatformView::renderHook(PanelsRenderHook::SCRIPTS_AFTER, scopes: $renderHookScopes) }}

        {{ PlatformView::renderHook(PanelsRenderHook::BODY_END, scopes: $renderHookScopes) }}
    </body>
</html>
