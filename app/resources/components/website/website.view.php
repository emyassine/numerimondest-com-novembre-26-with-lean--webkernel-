@props([
    'title' => 'Numerimondes — Ideas, Strategy, Execution',
    'description' => '',
    'canonical' => '',
    'locale' => 'en',
    'favicon' => 'brand/pwa-icon-192.png',
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
    $preloadImages = [];
    if ($studioPage === 'about') {
        $preloadImages[] = 'about/hero.jpg';
    } elseif (str_starts_with($studioPage, 'projects/')) {
        $allProjects = \App\Studio\ProjectCatalog::initiatives();
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
    $deploymentNoticePath = base_path('deploy.inc.txt');
    $deploymentNotice = is_file($deploymentNoticePath) ? file_get_contents($deploymentNoticePath) : '';
    $deploymentNotice = is_string($deploymentNotice) ? trim(str_replace('--', '—', $deploymentNotice)) : '';
@endphp
<!doctype html>
@if ($deploymentNotice !== '')
<!-- {!! $deploymentNotice !!} -->
@endif
<html lang="{{ $locale }}" data-theme="nm-studio" data-mode="{{ $forceLightMode ? 'light' : 'system' }}" data-srs-mode="{{ $forceLightMode ? 'light' : '' }}" class="{{ $forceLightMode ? 'light' : '' }}" data-currency="{{ $currency }}" style="color-scheme:{{ $forceLightMode ? 'light' : 'light dark' }};--nm-studio-background:{{ $studioBackground }};background-color:{{ $studioBackground }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="{{ $viewportContent }}">
    <meta name="color-scheme" content="{{ $forceLightMode ? 'light' : 'light dark' }}">
    <meta name="description" content="{{ $description }}">
    <meta name="theme-color" content="{{ $studioBackground }}">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="sitemap" type="application/xml" href="/sitemap.xml">
    <link rel="alternate" type="application/rss+xml" title="Numerimondes updates" href="/rss.xml">
    <link rel="apple-touch-icon" href="{{ simple_site('studio', $favicon) }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Numerimondes">
    <script>try { localStorage.setItem('platform:theme:mode', '{{ $forceLightMode ? 'light' : 'system' }}'); } catch {}</script>
    <link rel="icon" type="image/png" href="{{ simple_site('studio', $favicon) }}">
    @foreach ($preloadImages as $preloadImage)
        <link rel="preload" as="image" href="{{ simple_site('studio', $preloadImage) }}">
    @endforeach
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
    <style>
        @font-face { font-family: BrownLLMain; src: url('{{ \Webkernel\Getters\GetAsset::url('numerimondes/website', 'app/resources/assets/fonts/BrownLLMain-400-normal.woff') }}') format('woff'); font-style: normal; font-weight: 400; font-display: block; }
    </style>
</head>
<body>
<a class="nm-skip" href="#main">Skip to content</a>
<x-studio::website.navigation
    :site-prefix="$sitePrefix"
    :home="$home"
    :shortlist-count="$shortlistCount"
    :currency="$currency"
    :currency-options="$currencyOptions"
    :currency-config="$currencyConfig"
    :request-path="$requestPath"
    :studio-config="$studioConfig"
>
    <div id="main">{{ $slot }}</div>
</x-studio::website.navigation>
<script defer src="/assets/dependable-ui/dependable-ui.js"></script>
@if ($loadWebkernelPlatformTheme)
    <script defer src="{{ \Webkernel\Getters\GetAsset::url('webkernel/platform-support', 'dist/index.js') }}"></script>
@endif
<script defer src="{{ \Webkernel\Getters\GetAsset::url('numerimondes/website', 'app/resources/js/dependable-ui.themes.js') }}"></script>
<script defer src="{{ \Webkernel\Getters\GetAsset::url('numerimondes/website', 'app/resources/js/shortlist.js') }}"></script>
<script defer src="{{ \Webkernel\Getters\GetAsset::url('numerimondes/website', 'app/resources/js/pwa.js') }}"></script>
@if ($forceLightMode)
    <script>
        const publicThemeRoot = document.documentElement;
        const enforcePublicLightTheme = () => {
            if (publicThemeRoot.classList.contains('dark')) publicThemeRoot.classList.remove('dark');
            if (!publicThemeRoot.classList.contains('light')) publicThemeRoot.classList.add('light');
            if (publicThemeRoot.dataset.mode !== 'light') publicThemeRoot.dataset.mode = 'light';
            if (publicThemeRoot.dataset.srsMode !== 'light') publicThemeRoot.dataset.srsMode = 'light';
            if (publicThemeRoot.dataset.theme !== 'nm-studio') publicThemeRoot.dataset.theme = 'nm-studio';
        };
        new MutationObserver(enforcePublicLightTheme).observe(publicThemeRoot, { attributes: true, attributeFilter: ['class', 'data-theme', 'data-mode', 'data-srs-mode'] });
        enforcePublicLightTheme();
    </script>
@endif
</body>
</html>
