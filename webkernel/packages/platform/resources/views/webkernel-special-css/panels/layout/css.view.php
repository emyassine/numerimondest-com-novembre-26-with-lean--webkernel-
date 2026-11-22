{{--
    webkernel-platform::webkernel-special-css.panels.layout.css
    ────────────────────────────
    Entry point — includes only, no styles here.
    Injected at PanelsRenderHook::BODY_START (once per request, Octane-safe).
--}}

{{-- Design tokens --}}
@once
    <style nonce="{{ $csp_nonce ?? '' }}">
    :not(.fi-body-has-topbar) .fi-sidebar-header .fi-logo {
        margin-inline-start: calc(var(--spacing) * 1) !important;
    }
    </style>
    @include('webkernel-platform::webkernel-special-css.panels.layout._tokens')
    {{-- Typography (WTS) is injected in HEAD via PlatformRenderHooks::STYLES_AFTER --}}

    {{-- Page shell --}}
    @include('webkernel-platform::webkernel-special-css.panels.layout._page')

    {{-- Topbar --}}
    @include('webkernel-platform::webkernel-special-css.panels.layout.topbar._base')
    @include('webkernel-platform::webkernel-special-css.panels.layout.topbar._colors')

    {{-- Sidebar --}}
    @include('webkernel-platform::webkernel-special-css.panels.layout.sidebar._base')
    @include('webkernel-platform::webkernel-special-css.panels.layout.sidebar._items')
    @include('webkernel-platform::webkernel-special-css.panels.layout.sidebar._desktop')

    {{-- Main content area --}}
    @include('webkernel-platform::webkernel-special-css.panels.layout._main')

    {{-- Global components --}}
    @include('webkernel-platform::webkernel-special-css.panels.layout._table')
    @include('webkernel-platform::webkernel-special-css.panels.layout._scrollbar')
    {{-- Opt-in utilities (hover:elevate, hover:primary-border, …) --}}
    @include('webkernel-platform::webkernel-special-css.panels.layout.utility-classes.index')

    {{-- Stats elements --}}
    @include('webkernel-platform::webkernel-special-css.panels.layout.components._stats')
    @include('webkernel-platform::webkernel-special-css.panels.layout.components.triptych')

    {{-- JS hooks --}}
    @include('webkernel-platform::webkernel-special-css.panels.layout._script')

    <style nonce="{{ $csp_nonce ?? '' }}">
    /* Landmark focus target for skip-link — no giant page outline */
        #fi-main-content:focus,
        #fi-main-content:focus-visible,
        .fi-main:focus,
        .fi-main:focus-visible,
        .fi-simple-main:focus,
        .fi-simple-main:focus-visible {
            outline: none;
        }
    </style>


    @if(false)
        <style nonce="{{ $csp_nonce ?? '' }}">
        .fi-page-main::before {
            content: "";
            display: block;
            height: 200px; /* hauteur fixe ou ajustable */
            background-image: url("https://images.blush.design/b4cde6b173a7719b63c170a6a9f58ee0?w=920&auto=compress&cs=srgb");
            background-size: contain;     /* garde les proportions */
            background-repeat: no-repeat;
            background-position: center;
        }

        /* Variante en dark mode : inversion des couleurs */
        .dark .fi-page-main::before,
        [data-theme="dark"] .fi-page-main::before {
            filter: invert(0.7) brightness(1.2) contrast(0.9);
        }


        </style>
    @endif
@endonce
