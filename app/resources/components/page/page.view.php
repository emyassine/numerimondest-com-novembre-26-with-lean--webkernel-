@props([
    'title' => 'Numerimondes — Ideas, Strategy, Execution',
    'description' => '',
    'pageWidth' => 'marketplace',
])

@php
    $site = \Webkernel\SimpleSite\Site\SiteRegistry::forCurrentRequest();
    $sitePrefix = rtrim((string) ($site['prefix'] ?? ''), '/');
    $requestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    $requestPath = is_string($requestPath) ? $requestPath : '/';
    $pagePath = $sitePrefix !== '' && str_starts_with($requestPath, $sitePrefix)
        ? substr($requestPath, strlen($sitePrefix))
        : $requestPath;
    $pagePath = trim($pagePath, '/');
    $canonicalPath = $pagePath === '' ? '/' : '/'.$pagePath.'/';
    $siteBaseUrl = $site['canonical_url'] ?? config('app.url', '');
    $siteUrl = rtrim((string) $siteBaseUrl, '/').($sitePrefix === '' ? '' : '/'.trim($sitePrefix, '/'));
    $canonical = $siteUrl.$canonicalPath;
    $locale = \Webkernel\I18n\I18nContext::get_locale();
    $locale = $locale !== '' ? $locale : (string) ($site['fallback_locale'] ?? 'en');
    $siteUi = is_array($site['ui'] ?? null) ? $site['ui'] : [];

    $isMarketplaceWidth = $pageWidth === 'marketplace';
    $isUnboundedWidth = $pageWidth === null;
    $pageWidthStyle = '';

    if (! $isMarketplaceWidth && ! $isUnboundedWidth) {
        $normalizePageWidth = static function (mixed $value): ?string {
            if (is_numeric($value)) {
                return $value.'px';
            }

            if (is_string($value) && preg_match('/^\d+(?:\.\d+)?(?:px|rem|em|vw|%)?$/', trim($value))) {
                return trim($value);
            }

            return null;
        };

        if (is_array($pageWidth)) {
            $desktopPageWidth = $normalizePageWidth($pageWidth['desktop'] ?? $pageWidth['tablet'] ?? null);
            $tabletPageWidth = $normalizePageWidth($pageWidth['tablet'] ?? $desktopPageWidth);
            $mobilePageWidth = $normalizePageWidth($pageWidth['mobile'] ?? $tabletPageWidth);
        } else {
            $desktopPageWidth = $normalizePageWidth($pageWidth);
            $tabletPageWidth = $desktopPageWidth;
            $mobilePageWidth = $desktopPageWidth;
        }

        if ($desktopPageWidth === null || $tabletPageWidth === null || $mobilePageWidth === null) {
            throw new \InvalidArgumentException('The page-width prop must be null, a CSS length, or a responsive array of CSS lengths.');
        }

        $pageWidthStyle = '--studio-page-width-desktop: '.$desktopPageWidth.'; --studio-page-width-tablet: '.$tabletPageWidth.'; --studio-page-width-mobile: '.$mobilePageWidth.';';
    }
@endphp

<x-studio::website
    :title="$title"
    :description="$description"
    :canonical="$canonical"
    :locale="$locale"
    :site-ui="$siteUi"
    :site-prefix="$sitePrefix"
>
    <style>
        .studio-page-frame { box-sizing:border-box; width:100%; margin-inline:auto; padding-inline:50px; }
        .studio-page-frame--marketplace { width:calc(100% - 546px); max-width:1154px; }
        .studio-page-frame--custom-width { max-width:calc(var(--studio-page-width-desktop) + 100px); }
        .studio-page-frame--unbounded { max-width:none; }
        .studio-page-frame.bleed-page { width:100%; max-width:none; margin:0; padding-inline:0; }
        .nm-page { width: 100%; margin: 0; padding: 0; }
        .nm-page [x-cloak] { display: none !important; }
        .nm-page h1,
        .nm-page h2,
        .nm-page h3 {
            font-family: var(--nm-title);
            font-weight: 400;
            text-transform: uppercase;
            letter-spacing: .19em;
        }
        .nm-page-heading {
            margin: 0;
            min-height: 60vh;
            display: grid;
            place-items: center;
            text-align: center;
        }
        .nm-page-heading h1 {
            margin: 0;
            font: 400 16px/1.63 var(--nm-title);
            letter-spacing: .19em;
            text-transform: uppercase;
        }
        .nm-page-heading h1.silky-ri {
            font: 400 18px/1.63 Romie, Georgia, serif;
            font-style: italic;
            letter-spacing: normal;
            text-transform: none;
        }
        @media (max-width: 700px) {
            .studio-page-frame { width:100%; padding-inline:20px; }
            .studio-page-frame--custom-width { max-width:calc(var(--studio-page-width-mobile) + 40px); }
            .studio-page-frame.bleed-page { padding-inline:0; }
            .nm-page-heading { min-height: 55vh; }
        }
        @media (min-width:701px) and (max-width:900px) {
            .studio-page-frame--marketplace { width:calc(100% - 229px); max-width:none; }
            .studio-page-frame--custom-width { max-width:calc(var(--studio-page-width-tablet) + 100px); }
        }
    </style>
    <div {{ $attributes->class([
        'studio-page-frame',
        'studio-page-frame--marketplace' => $isMarketplaceWidth,
        'studio-page-frame--custom-width' => ! $isMarketplaceWidth && ! $isUnboundedWidth,
        'studio-page-frame--unbounded' => $isUnboundedWidth,
    ])->merge(['style' => $pageWidthStyle]) }}>{{ $slot }}</div>
</x-studio::website>
