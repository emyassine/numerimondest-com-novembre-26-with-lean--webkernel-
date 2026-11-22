<style nonce="{{ $csp_nonce ?? '' }}">
    .logo-before {
        -webkit-touch-callout: none;
        -webkit-user-select: none;
        user-select: none;
        display: flex; padding: 0.5rem; justify-content: center; align-items: center;
    }
    @media (min-width: 1024px) {
        .logo-before { display: none; }
    }
</style>
<div class="logo-before">

    @if ($homeUrl = platform()->getHomeUrl())
        <a {{ \Webkernel\Platform\Support\generate_href_html($homeUrl) }}>
            <x-webkernel-platform::logo />
        </a>
    @else
        <x-webkernel-platform::logo />
    @endif
</div>
