<?php declare(strict_types=1);

namespace Webkernel\SimpleSite\Http;

use RuntimeException;
use Webkernel\Config\Config;
use Webkernel\HttpFoundation\RedirectResponse;
use Webkernel\HttpFoundation\Response;
use Webkernel\SimpleSite\Site\PageDiscovery;
use Webkernel\SimpleSite\Site\SiteFeedGenerator;
use Webkernel\SimpleSite\Site\SiteRegistry;
use Webkernel\I18n\I18nContext;
use Webkernel\Route\Request;
use Webkernel\View\View;

final class SiteController
{
    public static function home(): Response
    {
        return self::render('');
    }

    public static function page(string $path): Response
    {
        return self::render($path);
    }

    public static function setLocale(Request $request): RedirectResponse
    {
        $site = SiteRegistry::forCurrentRequest();
        $locales = $site['locales'] ?? [];
        $locales = is_array($locales) ? array_values(array_filter($locales, 'is_string')) : [];
        $fallback = $site['fallback_locale'] ?? 'en';
        if (is_string($fallback) && ! in_array($fallback, $locales, true)) {
            $locales[] = $fallback;
        }

        $locale = $request->input('locale', '');
        if (is_string($locale) && in_array($locale, $locales, true)) {
            I18nContext::persist($locale);
        }

        $prefix = rtrim((string) ($site['prefix'] ?? ''), '/');
        $returnTo = $request->input('return_to', '');
        if (is_string($returnTo)) {
            $parts = parse_url($returnTo);
            $path = is_array($parts) ? ($parts['path'] ?? '') : '';
            if (is_string($path)
                && str_starts_with($path, '/')
                && ! str_starts_with($path, '//')
                && ($path === $prefix || str_starts_with($path, $prefix.'/'))
            ) {
                $target = $path;
                if (isset($parts['query']) && is_string($parts['query'])) {
                    $target .= '?'.$parts['query'];
                }

                return new RedirectResponse($target);
            }
        }

        return new RedirectResponse($prefix.'/');
    }

    public static function languageLanding(): RedirectResponse
    {
        $site = SiteRegistry::forCurrentRequest();
        return new RedirectResponse(rtrim((string) ($site['prefix'] ?? ''), '/').'/');
    }

    public static function setCurrency(Request $request): RedirectResponse
    {
        $site = SiteRegistry::forCurrentRequest();
        $currencyConfigPath = $site['currency_config'] ?? null;
        $currencyConfig = is_string($currencyConfigPath) ? Config::get($currencyConfigPath, []) : [];
        $options = is_array($currencyConfig) ? ($currencyConfig['options'] ?? []) : [];
        $cookieName = is_array($currencyConfig) ? ($currencyConfig['cookie'] ?? 'webkernel_currency') : 'webkernel_currency';
        $currency = $request->input('currency', '');

        if (is_string($cookieName)
            && preg_match('/\\A[a-zA-Z0-9_-]{1,64}\\z/', $cookieName) === 1
            && is_array($options)
            && is_string($currency)
            && isset($options[$currency])
        ) {
            $prefix = rtrim((string) ($site['prefix'] ?? ''), '/');
            $secure = ! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            setcookie($cookieName, $currency, [
                'expires' => time() + 365 * 86400,
                'path' => $prefix === '' ? '/' : $prefix.'/',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            $_COOKIE[$cookieName] = $currency;
        }

        $prefix = rtrim((string) ($site['prefix'] ?? ''), '/');
        $returnTo = $request->input('return_to', '');
        if (is_string($returnTo)) {
            $parts = parse_url($returnTo);
            $path = is_array($parts) ? ($parts['path'] ?? '') : '';
            if (is_string($path)
                && str_starts_with($path, '/')
                && ! str_starts_with($path, '//')
                && ($path === $prefix || str_starts_with($path, $prefix.'/'))
            ) {
                $target = $path;
                if (isset($parts['query']) && is_string($parts['query'])) {
                    $target .= '?'.$parts['query'];
                }

                return new RedirectResponse($target);
            }
        }

        return new RedirectResponse($prefix.'/');
    }

    public static function currencyLanding(): RedirectResponse
    {
        $site = SiteRegistry::forCurrentRequest();

        return new RedirectResponse(rtrim((string) ($site['prefix'] ?? ''), '/').'/');
    }

    public static function sitemap(): Response
    {
        $site = SiteRegistry::forCurrentRequest();
        return self::sitemapForSites([$site]);
    }

    public static function rootSitemap(): Response
    {
        $sites = Config::get('simple-site.sites', []);
        if (! is_array($sites)) {
            $sites = [];
        }

        return self::sitemapForSites(array_values(array_filter($sites, 'is_array')));
    }

    private static function sitemapForSites(array $sites): Response
    {
        return new Response(
            SiteFeedGenerator::sitemap($sites, (string) Config::get('app.url', '')),
            200,
            ['Content-Type' => 'application/xml; charset=UTF-8'],
        );
    }

    public static function rss(): Response
    {
        $site = SiteRegistry::forCurrentRequest();

        return self::rssForSites([$site]);
    }

    public static function rootRss(): Response
    {
        $sites = Config::get('simple-site.sites', []);
        if (! is_array($sites)) {
            $sites = [];
        }
        $sites = array_filter($sites, 'is_array');
        foreach ($sites as $site) {
            if (trim((string) ($site['prefix'] ?? ''), '/') === '') {
                return self::rssForSites([$site]);
            }
        }
        foreach ($sites as $site) {
            if (($site['default_feed'] ?? false) === true) {
                return self::rssForSites([$site]);
            }
        }

        $firstSite = reset($sites);

        return self::rssForSites(is_array($firstSite) ? [$firstSite] : []);
    }

    private static function rssForSites(array $sites): Response
    {
        return new Response(
            SiteFeedGenerator::rss($sites, (string) Config::get('app.url', '')),
            200,
            ['Content-Type' => 'application/rss+xml; charset=UTF-8'],
        );
    }

    public static function robots(): Response
    {
        $robotsFile = Config::get('simple-site.robots_file');
        if (is_string($robotsFile) && is_file($robotsFile) && is_readable($robotsFile)) {
            $content = file_get_contents($robotsFile);
            if (is_string($content)) {
                return new Response($content, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
            }
        }

        $sites = Config::get('simple-site.sites', []);
        $crawlers = Config::get('simple-site.disallowed_ai_crawlers', []);
        $crawlers = is_array($crawlers) ? $crawlers : [];
        $lines = [
            '# Automated AI crawler preferences. These rules request compliance; they do not provide access control.',
        ];
        foreach ($crawlers as $crawler) {
            if (is_string($crawler) && preg_match('/\A[A-Za-z0-9._-]+\z/', $crawler)) {
                $lines[] = 'User-agent: '.$crawler;
                $lines[] = 'Disallow: /';
            }
        }
        $lines[] = '';
        $lines[] = '# Preserve ordinary search engine access.';
        $lines[] = 'User-agent: *';
        $lines[] = 'Allow: /';
        if (is_array($sites)) {
            foreach ($sites as $site) {
                if (! is_array($site)) { continue; }
                $lines[] = 'Sitemap: '.self::siteUrl($site).'/sitemap.xml';
            }
        }

        return new Response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public static function terms(): RedirectResponse|Response
    {
        $sites = Config::get('simple-site.sites', []);
        if (is_array($sites)) {
            foreach ($sites as $siteId => $site) {
                $page = is_array($site) ? ($site['terms_page'] ?? null) : null;
                if (is_string($siteId) && is_string($page) && trim($page, '/') !== '') {
                    return new RedirectResponse(simple_site($siteId, $page), 301);
                }
            }
        }

        return new Response('Terms page is not configured.', 404, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public static function tdmrep(): Response
    {
        $policyFile = Config::get('simple-site.tdmrep_file');
        if (is_string($policyFile) && is_file($policyFile) && is_readable($policyFile)) {
            $policy = file_get_contents($policyFile);
            if (is_string($policy)) {
                return new Response($policy, 200, ['Content-Type' => 'application/tdmrep+json; charset=UTF-8']);
            }
        }

        return new Response('TDM reservation policy is not configured.', 404, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public static function llms(): Response
    {
        $site = SiteRegistry::forCurrentRequest();
        $source = $site['llm'] ?? null;
        if (is_string($source) && is_file($source)) {
            $content = file_get_contents($source);
            if (is_string($content)) {
                return new Response($content, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
            }
        } elseif (is_string($source)) {
            return new Response($source, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        return new Response('llms.txt is not configured.\n', 404, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    private static function render(string $path): Response
    {
        try {
            $site = SiteRegistry::forCurrentRequest();
            $asset = PageDiscovery::publicAsset($site, $path);
            if ($asset !== null) {
                $modifiedAt = filemtime($asset['file']);
                $size = filesize($asset['file']);
                $inode = fileinode($asset['file']);
                if (! is_int($modifiedAt) || ! is_int($size) || ! is_int($inode)) {
                    return new Response('', 404, ['Content-Type' => 'text/html; charset=UTF-8']);
                }
                $etag = 'W/"'.hash('sha256', $modifiedAt.':'.$size.':'.$inode).'"';
                $cacheControl = str_starts_with($asset['mime_type'], 'image/') || str_starts_with($asset['mime_type'], 'video/')
                    ? 'public, max-age=86400, stale-while-revalidate=604800'
                    : 'public, max-age=0, must-revalidate';
                $headers = [
                    'Content-Type' => $asset['mime_type'],
                    'X-Content-Type-Options' => 'nosniff',
                    'Cache-Control' => $cacheControl,
                    'ETag' => $etag,
                    'Last-Modified' => gmdate('D, d M Y H:i:s', $modifiedAt).' GMT',
                ];
                $ifNoneMatch = trim((string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? ''));
                $notModified = $ifNoneMatch !== ''
                    ? ($ifNoneMatch === '*' || in_array($etag, array_map('trim', explode(',', $ifNoneMatch)), true))
                    : (strtotime((string) ($_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '')) >= $modifiedAt);
                if ($notModified) {
                    return new Response('', 304, $headers);
                }
                $contents = file_get_contents($asset['file']);
                if (is_string($contents)) {
                    $headers['Content-Length'] = (string) strlen($contents);
                    if ($asset['mime_type'] === 'image/svg+xml') {
                        $headers['Content-Security-Policy'] = "default-src 'none'; style-src 'unsafe-inline'; sandbox";
                    }

                    return new Response($contents, 200, $headers);
                }
            }
            $page = PageDiscovery::find($site, $path);
        } catch (RuntimeException $exception) {
            throw $exception;
        }
        if ($page === null) {
            return new Response('', 404, ['Content-Type' => 'text/html; charset=UTF-8']);
        }

        $locale = self::applyLocale($site);

        $prefix = rtrim((string) ($site['prefix'] ?? ''), '/');
        $canonical = $page['props']['canonical'] ?? self::pageUrl($site, $page['path']);
        if (is_string($canonical) && str_starts_with($canonical, '/')) {
            $canonical = rtrim(self::siteUrl($site), '/').$canonical;
        }
        $props = array_replace([
            'title' => 'Untitled page',
            'description' => '',
            'canonical' => $canonical,
            'robots' => 'index,follow',
            'open_graph' => [],
            'twitter' => [],
            'schema' => [],
            'google' => $site['google'] ?? [],
            'ogImage' => $page['props']['ogImage'] ?? [],
            'siteOgImage' => $site['og_image'] ?? [],
            'locale' => $locale,
            'alternate_locales' => [],
            'updated_at' => null,
            'site' => $site,
            'site_ui' => is_array($site['ui'] ?? null) ? $site['ui'] : [],
            'page_path' => $page['path'],
            'page_url' => self::pageUrl($site, $page['path']),
            'site_prefix' => $prefix,
        ], $page['props']);
        $html = View::make($page['view'], $props)->render();

        return new Response($html, (int) ($props['status'] ?? 200), ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    private static function applyLocale(array $site): string
    {
        $locales = $site['locales'] ?? [];
        $fallback = $site['fallback_locale'] ?? 'en';
        if (! is_array($locales)) { $locales = []; }
        $locales = array_values(array_filter($locales, 'is_string'));
        if (! in_array($fallback, $locales, true)) { $locales[] = (string) $fallback; }
        $accepted = strtolower((string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''));
        $locale = I18nContext::get_locale();
        if (! in_array($locale, $locales, true)) {
            $locale = (string) $fallback;
        }
        $hasPreferredLocaleCookie = isset($_COOKIE[I18nContext::COOKIE])
            && in_array($_COOKIE[I18nContext::COOKIE], $locales, true);
        foreach ($locales as $candidate) {
            if (preg_match('/(?:^|[,;\s])'.preg_quote(strtolower($candidate), '/').'(?:[-,;\s]|$)/', $accepted) === 1) {
                if (! $hasPreferredLocaleCookie) {
                    $locale = $candidate;
                }
                break;
            }
        }
        I18nContext::set_locale($locale);

        return $locale;
    }

    private static function pageUrl(array $site, string $path): string
    {
        $base = self::siteUrl($site);
        $relative = trim($path, '/');
        if ($relative === '') {
            return $base.'/';
        }
        return $base.'/'.$relative.'/';
    }

    private static function siteUrl(array $site): string
    {
        $configuredUrl = $site['canonical_url'] ?? null;
        $appUrl = rtrim(is_string($configuredUrl) && filter_var($configuredUrl, FILTER_VALIDATE_URL) !== false
            ? $configuredUrl
            : (string) Config::get('app.url', ''), '/');
        $prefix = trim((string) ($site['prefix'] ?? ''), '/');
        return $appUrl.($prefix === '' ? '' : '/'.$prefix);
    }
}
