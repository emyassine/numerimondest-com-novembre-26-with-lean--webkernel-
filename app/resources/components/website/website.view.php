@props([
    'title' => 'Numerimondes — Ideas, Strategy, Execution',
    'description' => '',
    'canonical' => '',
    'locale' => 'en',
    'site_ui' => [],
])
@php
    \Webkernel\I18n\I18nContext::set_locale($locale);
    $studioConfig = config('nm-studio', []);
    $currencyConfig = is_array($studioConfig['currencies'] ?? null) ? $studioConfig['currencies'] : [];
    $currencyOptions = is_array($currencyConfig['options'] ?? null) ? $currencyConfig['options'] : [];
    $currencyCookie = is_string($currencyConfig['cookie'] ?? null) ? $currencyConfig['cookie'] : 'numerimondes_currency';
    $defaultCurrency = is_string($currencyConfig['default'] ?? null) ? $currencyConfig['default'] : 'MAD';
    $currency = is_string($_COOKIE[$currencyCookie] ?? null) && isset($currencyOptions[$_COOKIE[$currencyCookie]])
        ? $_COOKIE[$currencyCookie]
        : $defaultCurrency;
    if (! isset($currencyOptions[$currency])) {
        $currency = (string) array_key_first($currencyOptions);
    }
    $shortlistCount = count(\App\Studio\ServiceShortlist::all());
    $studioStylesheetPatterns = config('dependable-ui.stylesheets', []);
    $loadWebkernelPlatformTheme = (bool) config('dependable-ui.webkernel_platform_theme', false);
    $preventMobileZoom = (bool) config('dependable-ui.prevent_mobile_zoom', false);
    $forceLightMode = (bool) config('dependable-ui.force_light_mode', false);
    $viewportContent = 'width=device-width, initial-scale=1';
    if ($preventMobileZoom) {
        $viewportContent .= ', maximum-scale=1, user-scalable=no';
    }
    $studioStylesheets = [];
    foreach (is_array($studioStylesheetPatterns) ? $studioStylesheetPatterns : [] as $stylesheetPattern) {
        if (! is_string($stylesheetPattern)) {
            continue;
        }
        foreach (glob(base_path($stylesheetPattern)) ?: [] as $stylesheetPath) {
            if (is_file($stylesheetPath) && strtolower(pathinfo($stylesheetPath, PATHINFO_EXTENSION)) === 'css') {
                $studioStylesheets[] = substr($stylesheetPath, strlen(base_path()) + 1);
            }
        }
    }
    $studioStylesheets = array_values(array_unique($studioStylesheets));
    $studioBackground = $studioConfig['appearance']['background'] ?? '#f1f5f9';
    if (! is_string($studioBackground) || preg_match('/\A#[0-9a-fA-F]{6}\z/', $studioBackground) !== 1) {
        $studioBackground = '#f1f5f9';
    }
    $sitePrefix = rtrim((string) ($site_prefix ?? '/'), '/');
    $home = $sitePrefix.'/';
    $headerContrast = ($site_ui['header']['contrast'] ?? 'sections') === 'sections';
    $requestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
    $studioPage = trim(substr($requestPath, strlen($sitePrefix)), '/');
    $preloadImages = [
        'brand/numerimondes-logo-lightmode.png',
        'brand/numerimondes-logo-darkmode.png',
        'brand/numerimondes-logo-alone-lightmode.png',
        'brand/numerimondes-logo-alone-darkmode.png',
    ];
    if ($studioPage === '' || $studioPage === 'services') {
        foreach (\App\Studio\ServiceCatalog::all() as $service) {
            $preloadImages[] = $service['image_source'];
        }
    } elseif ($studioPage === 'about') {
        $preloadImages = [...$preloadImages, 'about/hero.jpg', 'about/founder.jpeg'];
    } elseif ($studioPage === 'projects') {
        foreach (\App\Studio\ProjectCatalog::initiatives() as $project) {
            $preloadImages[] = $project['card_image'];
        }
    } elseif (str_starts_with($studioPage, 'projects/')) {
        $allProjects = \App\Studio\ProjectCatalog::initiatives();
        foreach ($allProjects as $project) {
            $preloadImages[] = $project['card_image'];
        }
        $projectSlug = substr($studioPage, strlen('projects/'));
        if (isset($allProjects[$projectSlug])) {
            $preloadImages[] = $allProjects[$projectSlug]['cover'];
            if (! empty($allProjects[$projectSlug]['logo'])) {
                $preloadImages[] = $allProjects[$projectSlug]['logo'];
            }
        }
    } elseif (str_starts_with($studioPage, 'services/')) {
        $serviceSlug = substr($studioPage, strlen('services/'));
        $preloadImages[] = \App\Studio\ServiceCatalog::find($serviceSlug)['image_source'];
    }
    $preloadImages = array_values(array_unique($preloadImages));
@endphp
<!doctype html>
<html lang="{{ $locale }}" data-theme="nm-studio" data-currency="{{ $currency }}" style="color-scheme:{{ $forceLightMode ? 'light' : 'light dark' }};--nm-studio-background:{{ $studioBackground }};background-color:{{ $studioBackground }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="{{ $viewportContent }}">
    <meta name="color-scheme" content="{{ $forceLightMode ? 'light' : 'light dark' }}">
    <meta name="description" content="{{ $description }}">
    <meta name="theme-color" content="{{ $studioBackground }}">
    <script nonce="{{ $csp_nonce ?? '' }}">try { localStorage.setItem('platform:theme:mode', '{{ $forceLightMode ? 'light' : 'system' }}'); } catch {}</script>
    <link rel="icon" type="image/png" href="{{ simple_site('studio', 'brand/pwa-icon-192.png') }}">
    @foreach ($preloadImages as $preloadImage)
        <link rel="preload" as="image" href="{{ simple_site('studio', $preloadImage) }}">
    @endforeach
    <link rel="preload" as="font" type="font/woff" crossorigin href="{{ \Webkernel\Getters\GetAsset::url('numerimondes/website', 'app/resources/assets/fonts/Simon-400-normal.woff') }}">
    <link rel="preload" as="font" type="font/woff" crossorigin href="{{ \Webkernel\Getters\GetAsset::url('numerimondes/website', 'app/resources/assets/fonts/BrownLLMain-400-normal.woff') }}">
    @if ($studioPage === '' || $studioPage === 'contact')
        <link rel="preload" as="font" type="font/woff2" crossorigin href="/fonts/Romie-MediumItalic.woff2">
    @elseif (str_starts_with($studioPage, 'projects/'))
        <link rel="preload" as="font" type="font/woff2" crossorigin href="/fonts/Romie-Medium.woff2">
    @endif
    <link rel="canonical" href="{{ $canonical }}">
    <link rel="stylesheet" href="/assets/dependable-ui/dependable-ui.css">
    <link rel="stylesheet" href="{{ \Webkernel\Getters\GetAsset::url('numerimondes/website', 'app/resources/css/dependable-ui.themes.css') }}">
    <link rel="stylesheet" href="{{ \Webkernel\Getters\GetAsset::url('numerimondes/website', 'app/resources/css/numerimondes-silky.css') }}">
    @foreach ($studioStylesheets as $studioStylesheet)
        <link rel="stylesheet" href="{{ \Webkernel\Getters\GetAsset::url('numerimondes/website', $studioStylesheet) }}">
    @endforeach
    @if ($loadWebkernelPlatformTheme)
        <link rel="stylesheet" href="{{ \Webkernel\Getters\GetAsset::url('numerimondes/website', 'app/resources/css/platform-theme.css') }}">
    @endif
    <title>{{ $title }}</title>
    <style nonce="{{ $csp_nonce ?? '' }}">
        @font-face { font-family: Simon; src: url('{{ \Webkernel\Getters\GetAsset::url('numerimondes/website', 'app/resources/assets/fonts/Simon-400-normal.woff') }}') format('woff'); font-style: normal; font-weight: 400; font-display: block; }
        @font-face { font-family: BrownLLMain; src: url('{{ \Webkernel\Getters\GetAsset::url('numerimondes/website', 'app/resources/assets/fonts/BrownLLMain-400-normal.woff') }}') format('woff'); font-style: normal; font-weight: 400; font-display: block; }
    </style>
</head>
<body>
<a class="nm-skip" href="#main">Skip to content</a>
<header class="nm-header" data-scroll-state="down" data-logo-tone="dark">
    <nav class="nm-nav nm-nav--left" aria-label="Main navigation">
        <a href="{{ $sitePrefix }}/services">services</a>
        <a href="{{ $sitePrefix }}/projects">projects</a>
    </nav>
    <a class="nm-wordmark" href="{{ $home }}" aria-label="Numerimondes home">
        <img class="logo-wordmark-dark" src="{{ simple_site('studio', 'brand/numerimondes-logo-lightmode.png') }}" alt="" aria-hidden="true">
        <img class="logo-wordmark-light" src="{{ simple_site('studio', 'brand/numerimondes-logo-darkmode.png') }}" alt="" aria-hidden="true">
        <img class="logo-mark-dark" src="{{ simple_site('studio', 'brand/numerimondes-logo-alone-lightmode.png') }}" alt="" aria-hidden="true">
        <img class="logo-mark-light" src="{{ simple_site('studio', 'brand/numerimondes-logo-alone-darkmode.png') }}" alt="" aria-hidden="true">
    </a>
    <nav class="nm-nav nm-nav--right" aria-label="Site pages">
        <a href="{{ $sitePrefix }}/about">about</a>
        <a href="{{ $sitePrefix }}/shortlist" data-shortlist-link>shortlist
            @if ($shortlistCount > 0)
                <span data-shortlist-badge><x-webkernel-platform::badge color="gray" size="sm"><span data-shortlist-count>{{ $shortlistCount }}</span></x-webkernel-platform::badge></span>
            @endif
            <template data-shortlist-badge-template><span data-shortlist-badge><x-webkernel-platform::badge color="gray" size="sm"><span data-shortlist-count>0</span></x-webkernel-platform::badge></span></template>
        </a>
        <x-studio::currency-switcher
            :currency="$currency"
            :currency-options="$currencyOptions"
            :show-symbol="($currencyConfig['show_symbol'] ?? false) === true"
            :site-prefix="$sitePrefix"
            :current-path="$requestPath"
        />
        <a href="{{ $sitePrefix }}/contact">contact</a>
    </nav>
    <div class="nm-nav--compact">
        <x-webkernel-platform::dropdown placement="bottom-end" width="xs">
            <x-slot name="trigger">
                <x-webkernel-platform::icon-button
                    color="gray"
                    :icon="\Webkernel\Platform\Support\Icons\Heroicon::OutlinedBars3"
                    size="sm"
                    label="Open site menu"
                    class="nm-nav-menu-trigger"
                />
            </x-slot>

            <x-webkernel-platform::dropdown.list>
                <x-webkernel-platform::dropdown.list.item tag="a" :href="$sitePrefix.'/services'">Services</x-webkernel-platform::dropdown.list.item>
                <x-webkernel-platform::dropdown.list.item tag="a" :href="$sitePrefix.'/projects'">Projects</x-webkernel-platform::dropdown.list.item>
                <x-webkernel-platform::dropdown.list.item tag="a" :href="$sitePrefix.'/about'">About</x-webkernel-platform::dropdown.list.item>
                <x-webkernel-platform::dropdown.list.item tag="a" :href="$sitePrefix.'/shortlist'" :badge="$shortlistCount > 0 ? $shortlistCount : null" data-shortlist-link>Shortlist</x-webkernel-platform::dropdown.list.item>
                <x-webkernel-platform::dropdown.list.item tag="a" :href="$sitePrefix.'/contact'">Contact</x-webkernel-platform::dropdown.list.item>
            </x-webkernel-platform::dropdown.list>

            @if (count($currencyOptions) > 1)
                <x-webkernel-platform::dropdown.header>Currency · {{ $currency }}</x-webkernel-platform::dropdown.header>
                <form action="{{ rtrim($sitePrefix, '/') }}/currency" method="post">
                    @csrf
                    <input type="hidden" name="return_to" value="{{ $requestPath }}">
                    <x-webkernel-platform::dropdown.list>
                        @foreach ($currencyOptions as $currencyCode => $option)
                            <x-webkernel-platform::dropdown.list.item tag="button" type="submit" name="currency" :value="$currencyCode" :aria-current="$currency === $currencyCode ? 'true' : null">
                                <span class="nm-menu-currency-option">
                                    <span aria-hidden="true">{!! flag_markup($option['flag'] ?? '') !!}</span>
                                    <span>{{ $option['label'] ?? $currencyCode }}</span>
                                    <small>{{ $currencyCode }}</small>
                                </span>
                            </x-webkernel-platform::dropdown.list.item>
                        @endforeach
                    </x-webkernel-platform::dropdown.list>
                </form>
            @endif
        </x-webkernel-platform::dropdown>
    </div>
</header>
<div id="main">{{ $slot }}</div>
<footer class="nm-footer" id="contact" style="margin-top:5rem;">
    <span class="nm-footer-copy"  style="margin-top:3rem;margin-bottom:2rem;">
    © {{ $studioConfig['company']['name'] }} · {{ $studioConfig['company']['location'] }} — Page Load took {{ number_format(request_time(), 2, ',', ' ') . ' ms' }}</span>
    <div class="nm-footer-links">
        @foreach (($studioConfig['social'] ?? []) as $network => $url)
            <a href="{{ $url }}" target="_blank" rel="noreferrer">{{ $network }}</a>
        @endforeach
        <a href="{{ $studioConfig['crm']['client_space'] }}" target="_blank" rel="noreferrer">Client space</a>
        <a href="{{ $studioConfig['crm']['client_registration'] }}" target="_blank" rel="noreferrer">Create account</a>
        <a href="{{ $studioConfig['crm']['staff_login'] }}" target="_blank" rel="noreferrer">Staff login</a>
    </div>
    <a class="nm-footer-email" href="{{ $studioConfig['contact']['phone']['href'] }}">
        {{ $studioConfig['contact']['phone']['display'] }}
    </a>
    <a class="nm-footer-privacy" href="{{ $sitePrefix }}/privacy">Privacy policy</a>
    <a class="nm-footer-privacy" href="{{ $sitePrefix }}/pricing-policy">Pricing &amp; engagement</a>
</footer>
<script defer src="/assets/dependable-ui/dependable-ui.js"></script>
@if ($loadWebkernelPlatformTheme)
    <script defer src="{{ \Webkernel\Getters\GetAsset::url('webkernel/platform-support', 'dist/index.js') }}"></script>
@endif
<script defer src="{{ \Webkernel\Getters\GetAsset::url('numerimondes/website', 'app/resources/js/dependable-ui.themes.js') }}"></script>
<script defer src="{{ \Webkernel\Getters\GetAsset::url('numerimondes/website', 'app/resources/js/shortlist.js') }}"></script>
@if ($forceLightMode)
    <script nonce="{{ $csp_nonce ?? '' }}">
        const publicThemeRoot = document.documentElement;
        const enforcePublicLightTheme = () => {
            publicThemeRoot.classList.remove('dark');
            if (publicThemeRoot.dataset.theme !== 'nm-studio') publicThemeRoot.dataset.theme = 'nm-studio';
        };
        new MutationObserver(enforcePublicLightTheme).observe(publicThemeRoot, { attributes: true, attributeFilter: ['class', 'data-theme'] });
        enforcePublicLightTheme();
    </script>
@endif
</body>
</html>
