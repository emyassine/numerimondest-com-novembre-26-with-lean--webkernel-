@props([
    'title' => 'Numerimondes — Ideas, Strategy, Execution',
    'description' => '',
    'canonical' => '',
    'locale' => 'en',
    'favicon' => 'brand/pwa-icon-192.png',
    'site_ui' => [],
    'site_prefix' => '/',
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
    $sitePrefix = rtrim($site_prefix, '/');
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
@endphp
<!doctype html>
@php
    $deploymentIncludePath = base_path('deploy.inc.php');
    if (is_file($deploymentIncludePath)) {
        require $deploymentIncludePath;
    }
@endphp
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
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Numerimondes">
    <script>try { localStorage.setItem('platform:theme:mode', '{{ $forceLightMode ? 'light' : 'system' }}'); } catch {}</script>
    <link rel="icon" type="image/png" href="{{ simple_site('studio', $favicon) }}">
    @foreach ($preloadImages as $preloadImage)
        <link rel="preload" as="image" href="{{ simple_site('studio', $preloadImage) }}">
    @endforeach
    @php
        $fontAssets = [
            'brown' => [
                'href' => \Webkernel\Getters\GetAsset::url('numerimondes/website', 'app/resources/assets/fonts/BrownLLMain-400-normal.woff'),
                'type' => 'font/woff',
            ],
            'estedad' => [
                'href' => \Webkernel\Getters\GetAsset::url('numerimondes/website', 'app/resources/assets/fonts/Estedad-Regular.woff2'),
                'type' => 'font/woff2',
            ],
            'romie_italic' => ['href' => '/fonts/Romie-MediumItalic.woff2', 'type' => 'font/woff2'],
            'romie' => ['href' => '/fonts/Romie-Medium.woff2', 'type' => 'font/woff2'],
        ];
        $fontPreloads = [$fontAssets['brown'], $fontAssets['estedad']];
        if ($studioPage === '' || $studioPage === 'contact') {
            $fontPreloads[] = $fontAssets['romie_italic'];
        } elseif (str_starts_with($studioPage, 'projects/')) {
            $fontPreloads[] = $fontAssets['romie'];
        }
    @endphp
    @foreach ($fontPreloads as $fontPreload)
        <link rel="preload" as="font" type="{{ $fontPreload['type'] }}" crossorigin href="{{ $fontPreload['href'] }}">
    @endforeach
    <link rel="canonical" href="{{ $canonical }}">
    @php
        $stylesheets = [
            \Webkernel\Getters\GetAsset::url('numerimondes/website', 'app/resources/assets/dependable-ui/dependable-ui.css'),
            \Webkernel\Getters\GetAsset::url('numerimondes/website', 'app/resources/css/dependable-ui.themes.css'),
            \Webkernel\Getters\GetAsset::url('numerimondes/website', 'app/resources/css/numerimondes-silky.css'),
        ];
        foreach ($studioStylesheets as $studioStylesheet) {
            $stylesheets[] = \Webkernel\Getters\GetAsset::url('numerimondes/website', $studioStylesheet);
        }
        if ($loadWebkernelPlatformTheme) {
            $stylesheets[] = \Webkernel\Getters\GetAsset::url('numerimondes/website', 'app/resources/assets/webkernel/platform/theme.css');
        }
        $stylesheets = array_values(array_unique($stylesheets));
    @endphp
    @foreach ($stylesheets as $stylesheet)
        <link rel="stylesheet" href="{{ $stylesheet }}">
    @endforeach
    <title>{{ $title }}</title>
    <style>
    		[x-cloak]{display:none!important}

        @font-face { font-family: BrownLLMain; src: url('{{ $fontAssets['brown']['href'] }}') format('woff'); font-style: normal; font-weight: 400; font-display: block; }
        @font-face { font-family: Estedad; src: url('{{ $fontAssets['estedad']['href'] }}') format('woff2'); font-style: normal; font-weight: 400; font-display: swap; }
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
    <x-slot name="subheader">{{ $subheader ?? '' }}</x-slot>
    {{ $slot }}
</x-studio::website.navigation>
<script defer src="{{ \Webkernel\Getters\GetAsset::url('webkernel/platform-support', 'dist/index.js') }}"></script>
<script defer src="{{ \Webkernel\Getters\GetAsset::url('numerimondes/website', 'app/resources/js/alpine.js') }}"></script>
<script defer src="{{ \Webkernel\Getters\GetAsset::url('numerimondes/website', 'app/resources/assets/dependable-ui/dependable-ui.js') }}"></script>
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
