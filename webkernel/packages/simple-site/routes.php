<?php declare(strict_types=1);

use Webkernel\Config\Config;
use Webkernel\Route\Route;
use Webkernel\SimpleSite\Http\SiteController;

$sites = Config::get('simple-site.sites', []);
if (! is_array($sites)) {
    return;
}

Route::get('/robots.txt', [SiteController::class, 'robots']);
Route::get('/sitemap.xml', [SiteController::class, 'rootSitemap']);
Route::get('/rss.xml', [SiteController::class, 'rootRss']);
Route::get('/.well-known/tdmrep.json', [SiteController::class, 'tdmrep']);

$hasTermsPage = false;
foreach ($sites as $site) {
    if (is_array($site) && is_string($site['terms_page'] ?? null) && trim($site['terms_page'], '/') !== '') {
        $hasTermsPage = true;
        break;
    }
}
if ($hasTermsPage) {
    Route::get('/legal/terms/', [SiteController::class, 'terms']);
}

foreach ($sites as $siteKey => $site) {
    if (! is_string($siteKey) || ! preg_match('/\A[a-zA-Z0-9_-]+\z/', $siteKey) || ! is_array($site)) {
        continue;
    }
    $prefix = $site['prefix'] ?? null;
    if (! is_string($prefix) || ! str_starts_with($prefix, '/') || str_contains($prefix, '{')) {
        continue;
    }
    $prefix = rtrim($prefix, '/');
    if ($prefix === '') {
        $prefix = '/';
    }

    $routeBase = $prefix === '/' ? '' : $prefix;
    $routePath = static fn (string $path): string => $routeBase.'/'.ltrim($path, '/');

    Route::get($routePath(''), [SiteController::class, 'home']);
    Route::get($routePath('language'), [SiteController::class, 'languageLanding']);
    Route::post($routePath('language'), [SiteController::class, 'setLocale']);
    Route::get($routePath('currency'), [SiteController::class, 'currencyLanding']);
    Route::post($routePath('currency'), [SiteController::class, 'setCurrency']);
    if ($routeBase !== '') {
        Route::get($routePath('sitemap.xml'), [SiteController::class, 'sitemap']);
        Route::get($routePath('rss.xml'), [SiteController::class, 'rss']);
    }
    Route::get($routePath('llms.txt'), [SiteController::class, 'llms']);
    Route::get($routePath('{path:.*}'), [SiteController::class, 'page']);
}
