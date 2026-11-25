@props([
    'title' => 'Numerimondes — Ideas, Strategy, Execution',
    'description' => '',
    'subheader' => null,
    'page_width' => [
        'desktop' => 'min(calc(100vw - 646px), 1054px)',
        'tablet' => 'calc(100vw - 329px)',
        'mobile' => 'calc(100vw - 40px)',
    ],
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

    $isUnboundedWidth = $page_width === null;
    $pageWidthStyle = '';

    if (! $isUnboundedWidth) {
        $normalizePageWidth = static function (mixed $value): ?string {
            if (is_numeric($value)) {
                return $value.'px';
            }

            if (is_string($value)) {
                $value = trim($value);
                $isCssLength = preg_match('/^\d+(?:\.\d+)?(?:px|rem|em|vw|%)?$/', $value) === 1;
                $isResponsiveWidth = preg_match('/^(?:calc\(100vw - \d+(?:\.\d+)?px\)|min\(calc\(100vw - \d+(?:\.\d+)?px\), \d+(?:\.\d+)?px\))$/', $value) === 1;

                if ($isCssLength || $isResponsiveWidth) {
                    return $value;
                }
            }

            return null;
        };

        if (is_array($page_width)) {
            $desktopPageWidth = $normalizePageWidth($page_width['desktop'] ?? $page_width['tablet'] ?? null);
            $tabletPageWidth = $normalizePageWidth($page_width['tablet'] ?? $desktopPageWidth);
            $mobilePageWidth = $normalizePageWidth($page_width['mobile'] ?? $tabletPageWidth);
        } else {
            $desktopPageWidth = $normalizePageWidth($page_width);
            $tabletPageWidth = $desktopPageWidth;
            $mobilePageWidth = $desktopPageWidth;
        }

        if ($desktopPageWidth === null || $tabletPageWidth === null || $mobilePageWidth === null) {
            throw new \InvalidArgumentException('The page-width prop must be null, a CSS length, or a responsive array of supported CSS widths.');
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
    :site-prefix="$sitePrefix">
    <x-slot name="subheader">
        {{ $subheader }}
    </x-slot>
    <style>
        .studio-page-frame { box-sizing:border-box; width:100%; margin-inline:auto; padding-inline:50px; }
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
            .studio-page-frame--custom-width { max-width:calc(var(--studio-page-width-tablet) + 100px); }
        }
    </style>
    <div {{ $attributes->class([
        'studio-page-frame',
        'studio-page-frame--custom-width' => ! $isUnboundedWidth,
        'studio-page-frame--unbounded' => $isUnboundedWidth,
    ])->merge(['style' => $pageWidthStyle]) }}>{{ $slot }}</div>
</x-studio::website>
