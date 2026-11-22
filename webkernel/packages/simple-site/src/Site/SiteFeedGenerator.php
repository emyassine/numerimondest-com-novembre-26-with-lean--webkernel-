<?php declare(strict_types=1);

namespace Webkernel\SimpleSite\Site;

final class SiteFeedGenerator
{
    /** @param list<array<string, mixed>> $sites */
    public static function sitemap(array $sites, string $baseUrl): string
    {
        $localizedPages = self::localizedPageUrls($sites, $baseUrl);
        $urls = [];
        foreach ($sites as $site) {
            foreach (PageDiscovery::pages($site) as $page) {
                $props = $page['props'];
                if ((int) ($props['status'] ?? 200) >= 400 || str_contains(strtolower((string) ($props['robots'] ?? '')), 'noindex')) {
                    continue;
                }
                $url = self::pageUrl($site, $page['path'], $baseUrl);
                $canonical = $props['canonical'] ?? $url;
                if (is_string($canonical) && $canonical !== '') {
                    $url = str_starts_with($canonical, '/') ? rtrim(self::siteBaseUrl($site, $baseUrl), '/').$canonical : $canonical;
                }
                $lastmod = $props['updated_at'] ?? null;
                $alternates = self::localizedAlternates($site, $page, $localizedPages);
                $alternateXml = '';
                foreach ($alternates as $locale => $alternateUrl) {
                    $alternateXml .= '<xhtml:link rel="alternate" hreflang="'.self::xml($locale).'" href="'.self::xml($alternateUrl).'"/>';
                }
                if (count($alternates) > 1) {
                    $defaultLocale = (string) ($site['default_locale'] ?? $site['fallback_locale'] ?? array_key_first($alternates));
                    if (isset($alternates[$defaultLocale])) {
                        $alternateXml .= '<xhtml:link rel="alternate" hreflang="x-default" href="'.self::xml($alternates[$defaultLocale]).'"/>';
                    }
                }
                $urls[] = '<url><loc>'.self::xml($url).'</loc>'.(is_string($lastmod) && $lastmod !== '' ? '<lastmod>'.self::xml($lastmod).'</lastmod>' : '').$alternateXml.'</url>';
            }
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">'.implode('', $urls).'</urlset>'."\n";
    }

    /** @param list<array<string, mixed>> $sites */
    public static function rss(array $sites, string $baseUrl): string
    {
        $site = $sites[0] ?? [];
        $feedConfig = is_array($site['feed'] ?? null) ? $site['feed'] : [];
        $siteUrl = self::siteUrl($site, $baseUrl);
        $title = (string) ($feedConfig['title'] ?? $site['name'] ?? 'Site updates');
        $description = (string) ($feedConfig['description'] ?? 'Updates from '.($site['name'] ?? 'this site').'.');
        $language = (string) ($feedConfig['language'] ?? $site['locale'] ?? $site['fallback_locale'] ?? 'en');
        $items = [];

        foreach ([$site] as $currentSite) {
            foreach (PageDiscovery::pages($currentSite) as $page) {
                $props = $page['props'];
                if ((int) ($props['status'] ?? 200) >= 400
                    || str_contains(strtolower((string) ($props['robots'] ?? '')), 'noindex')
                    || ($props['rss'] ?? true) === false
                ) {
                    continue;
                }

                $url = self::pageUrl($currentSite, $page['path'], $baseUrl);
                $pageTitle = is_string($props['title'] ?? null) && $props['title'] !== ''
                    ? $props['title']
                    : self::titleFromPath($page['path'], (string) ($currentSite['name'] ?? $title));
                $pageDescription = is_string($props['description'] ?? null) ? $props['description'] : '';
                $timestamp = is_string($props['updated_at'] ?? null)
                    ? strtotime($props['updated_at'])
                    : (filemtime($page['file']) ?: time());
                $items[] = '<item><title>'.self::xml($pageTitle).'</title><link>'.self::xml($url).'</link><guid isPermaLink="true">'.self::xml($url).'</guid><description>'.self::xml($pageDescription).'</description><pubDate>'.gmdate(DATE_RSS, $timestamp ?: time()).'</pubDate></item>';
            }
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'."\n".
            '<rss version="2.0"><channel><title>'.self::xml($title).'</title><link>'.self::xml($siteUrl).'</link><description>'.self::xml($description).'</description><language>'.self::xml($language).'</language><lastBuildDate>'.gmdate(DATE_RSS).'</lastBuildDate>'.implode('', $items).'</channel></rss>'."\n";
    }

    private static function pageUrl(array $site, string $path, string $baseUrl): string
    {
        $prefix = trim((string) ($site['prefix'] ?? ''), '/');
        $url = rtrim(self::siteBaseUrl($site, $baseUrl), '/').($prefix === '' ? '' : '/'.$prefix);
        $relative = trim($path, '/');

        return $url.'/'.($relative === '' ? '' : $relative.'/');
    }

    private static function siteUrl(array $site, string $baseUrl): string
    {
        $prefix = trim((string) ($site['prefix'] ?? ''), '/');

        return rtrim(self::siteBaseUrl($site, $baseUrl), '/').($prefix === '' ? '' : '/'.$prefix).'/';
    }

    private static function siteBaseUrl(array $site, string $fallback): string
    {
        $canonicalUrl = $site['canonical_url'] ?? null;

        return is_string($canonicalUrl) && filter_var($canonicalUrl, FILTER_VALIDATE_URL) !== false
            ? $canonicalUrl
            : $fallback;
    }

    /** @param list<array<string, mixed>> $sites @return array<string, array<string, array<string, string>>> */
    private static function localizedPageUrls(array $sites, string $baseUrl): array
    {
        $localizedPages = [];
        foreach ($sites as $site) {
            $group = $site['translation_group'] ?? null;
            $locale = $site['locale'] ?? $site['fallback_locale'] ?? null;
            if (! is_string($group) || $group === '' || ! is_string($locale) || $locale === '') {
                continue;
            }
            foreach (PageDiscovery::pages($site) as $page) {
                $localizedPages[$group][$page['path']][$locale] = self::pageUrl($site, $page['path'], $baseUrl);
            }
        }

        return $localizedPages;
    }

    /** @param array<string, array<string, array<string, string>>> $localizedPages @return array<string, string> */
    private static function localizedAlternates(array $site, array $page, array $localizedPages): array
    {
        $group = $site['translation_group'] ?? null;
        $alternates = is_string($group) && $group !== ''
            ? ($localizedPages[$group][$page['path']] ?? [])
            : [];
        $explicit = $page['props']['alternate_locales'] ?? [];
        if (is_array($explicit)) {
            foreach ($explicit as $locale => $url) {
                if (is_string($locale) && is_string($url) && filter_var($url, FILTER_VALIDATE_URL) !== false) {
                    $alternates[$locale] = $url;
                }
            }
        }

        return $alternates;
    }

    private static function titleFromPath(string $path, string $siteName): string
    {
        if (trim($path, '/') === '') {
            return $siteName;
        }
        $words = preg_replace('/[-_]+/', ' ', trim($path, '/')) ?? trim($path, '/');

        return ucwords($words).' — '.$siteName;
    }

    private static function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
