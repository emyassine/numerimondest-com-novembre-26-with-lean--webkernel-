@props([
    'status' => 404,
    'locale' => 'en',
    'title' => 'Page not found',
    'description' => 'The page you are looking for does not exist or has been moved.',
    'accent' => 'gray',
    'icon' => 'search',
    'exception_message' => null,
])
@php
    $studioConfig = config('nm-studio', []);
    $preventMobileZoom = (bool) config('dependable-ui.prevent_mobile_zoom', false);
    $forceLightMode = (bool) config('dependable-ui.force_light_mode', false);
    $viewportContent = 'width=device-width, initial-scale=1';
    if ($preventMobileZoom) {
        $viewportContent .= ', maximum-scale=1, user-scalable=no';
    }
    $studioBackground = $studioConfig['appearance']['background'] ?? '#f1f5f9';
    if (! is_string($studioBackground) || preg_match('/\A#[0-9a-fA-F]{6}\z/', $studioBackground) !== 1) {
        $studioBackground = '#f1f5f9';
    }
@endphp
<!doctype html>
<html lang="{{ $locale }}" data-theme="nm-studio" data-mode="{{ $forceLightMode ? 'light' : 'system' }}" data-srs-mode="{{ $forceLightMode ? 'light' : '' }}" class="{{ $forceLightMode ? 'light' : '' }}" style="color-scheme:{{ $forceLightMode ? 'light' : 'light dark' }};--nm-studio-background:{{ $studioBackground }};background-color:{{ $studioBackground }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="{{ $viewportContent }}">
    <meta name="color-scheme" content="{{ $forceLightMode ? 'light' : 'light dark' }}">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="{{ $studioBackground }}">
    <link rel="icon" type="image/png" href="{{ simple_site('studio', 'brand/pwa-icon-192.png') }}">
    <link rel="preload" as="image" href="{{ simple_site('studio', 'brand/pwa-icon-192.png') }}">
    <link rel="preload" as="image" href="{{ simple_site('studio', 'brand/numerimondes-logo-alone-lightmode.png') }}">
    <link rel="preload" as="font" type="font/woff" crossorigin href="{{ \Webkernel\Getters\GetAsset::url('numerimondes/website', 'app/resources/assets/fonts/BrownLLMain-400-normal.woff') }}">
    <link rel="preload" as="font" type="font/woff2" crossorigin href="{{ \Webkernel\Getters\GetAsset::url('numerimondes/website', 'app/resources/assets/fonts/Romie-Medium.woff2') }}">
    <link rel="stylesheet" href="{{ \Webkernel\Getters\GetAsset::url('numerimondes/website', 'app/resources/css/dependable-ui.themes.css') }}">
    <title>{{ $status }} — Numerimondes</title>
    <style>
        @font-face { font-family: BrownLLMain; src: url('{{ \Webkernel\Getters\GetAsset::url('numerimondes/website', 'app/resources/assets/fonts/BrownLLMain-400-normal.woff') }}') format('woff'); font-style:normal; font-weight:400; font-display:block; }
        @font-face { font-family: Romie; src: url('{{ \Webkernel\Getters\GetAsset::url('numerimondes/website', 'app/resources/assets/fonts/Romie-Medium.woff2') }}') format('woff2'); font-style:normal; font-weight:500; font-display:block; }
        :root { --nm-studio-background:{{ $studioBackground }}; }
        * { box-sizing:border-box; }
        html,body { min-height:100%; margin:0; }
        body { min-height:100vh; display:grid; place-items:center; padding:30px; color:#111; background:var(--nm-paper); text-align:center; }
        .nm-error { width:min(100%,760px); display:flex; flex-direction:column; align-items:center; }
        .nm-error-brand { width:min(100%,280px); margin:0 auto; }
        .nm-error-logo { display:block; width:100%; height:auto; }
        .nm-error-logo--darkmode { display:none; }
        .nm-error-brand[data-tone="dark"] .nm-error-logo--lightmode { display:none; }
        .nm-error-brand[data-tone="dark"] .nm-error-logo--darkmode { display:block; }
        .nm-error-title { margin:42px 0 18px; font:500 16px/1.19 Romie,Georgia,serif; letter-spacing:.19em; text-transform:uppercase; }
        .nm-error-description { max-width:560px; margin:0; font:400 18px/1.22 BrownLLMain,Arial,sans-serif; letter-spacing:.02em; }
        @media(max-width:600px) { body { padding:22px; } .nm-error-brand { width:min(100%,230px); } .nm-error-title { margin-top:35px; font-size:13px; } .nm-error-description { font-size:16px; } }
    </style>
</head>
<body>
    <main class="nm-error" aria-labelledby="nm-error-title">
        <div class="nm-error-brand" style="max-width:85px;" data-tone="light" aria-label="Numerimondes">
            <img class="nm-error-logo nm-error-logo--lightmode" src="{{ simple_site('studio', 'brand/numerimondes-logo-alone-lightmode.png') }}" alt="Numerimondes">
            <img class="nm-error-logo nm-error-logo--darkmode" src="{{ simple_site('studio', 'brand/numerimondes-logo-alone-darkmode.png') }}" alt="" aria-hidden="true" loading="lazy">
        </div>
        <h1 class="nm-error-title" id="nm-error-title">{{ $title }}</h1>
        <p class="nm-error-description">{{ $description }}</p>
    </main>
</body>
</html>
