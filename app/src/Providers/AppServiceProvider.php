<?php declare(strict_types=1);

namespace App\Providers;

use Webkernel\Route\Route;
use Webkernel\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public const array ROUTE_BOOTSTRAPPERS = [[self::class, 'registerStudioRoutes']];

    public const array VIEWS = [
        'studio' => [__DIR__.'/../../resources/views'],
    ];

    public const array COMPONENTS = [
        'webkernel-platform' => [
            __DIR__.'/../../../internal/dependencies/webkernel/platform-support/resources/views/components',
        ],
        'studio' => [
            __DIR__.'/../../resources/components/hero',
            __DIR__.'/../../resources/components/inquiry-offer-card',
            __DIR__.'/../../resources/components/currency-switcher',
            __DIR__.'/../../resources/components/marketplace-card',
            __DIR__.'/../../resources/components/marketplace-list',
            __DIR__.'/../../resources/components/project-card',
            __DIR__.'/../../resources/components/project-detail',
            __DIR__.'/../../resources/components/showcase-card',
            __DIR__.'/../../resources/components/showcase-list',
            __DIR__.'/../../resources/components/service-detail',
            __DIR__.'/../../resources/components/service-inquiry-launch',
            __DIR__.'/../../resources/components/shortlist-button',
            __DIR__.'/../../resources/components/website',
        ],
    ];

    public const array PUBLIC_ASSETS = [
        __DIR__.'/../../resources/css',
        __DIR__.'/../../resources/js',
        __DIR__.'/../../resources/assets',
        __DIR__.'/../../resources/components',
    ];

    public static function registerStudioRoutes(): void
    {
        Route::post('/service-inquiry/select', [\App\Studio\ServiceInquiryController::class, 'select']);
        Route::post('/service-inquiry/project/remove', [\App\Studio\ServiceInquiryController::class, 'removeProject']);
        Route::post('/service-inquiry', [\App\Studio\ServiceInquiryController::class, 'submit']);
        Route::post('/shortlist/toggle', [\App\Studio\ShortlistController::class, 'toggle']);

        Route::permanent_redirect('/capabilities', '/services');

        $capabilityFiles = glob(base_path('app/resources/data/studio/capabilities/*.php')) ?: [];
        foreach ($capabilityFiles as $capabilityFile) {
            $capability = require $capabilityFile;
            $slug = is_array($capability) ? ($capability['slug'] ?? null) : null;

            if (is_string($slug) && $slug !== '') {
                Route::permanent_redirect('/capabilities/'.$slug, '/services');
                Route::permanent_redirect('/services/'.$slug, '/services');
            }
        }
    }

    /** Register Platform's fluent attribute helpers on public-site routes too. */
    public static function registerPlatformAttributeBagMacros(): void
    {
        \Webkernel\Platform\Support\Webkernel\AttributeBagMacros::register();
    }

}
