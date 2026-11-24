<?php declare(strict_types=1);

use Webkernel\Route\Route;

Route::get('/manifest.webmanifest', [\App\Studio\Http\PwaController::class, 'manifest']);
Route::get('/service-worker.js', [\App\Studio\Http\PwaController::class, 'serviceWorker']);

Route::post('/service-inquiry/select', [\App\Studio\Http\ServiceInquiryController::class, 'select']);
Route::post('/service-inquiry/project/remove', [\App\Studio\Http\ServiceInquiryController::class, 'removeProject']);
Route::post('/service-inquiry', [\App\Studio\Http\ServiceInquiryController::class, 'submit']);
Route::post('/shortlist/toggle', [\App\Studio\Http\ShortlistController::class, 'toggle']);
Route::post('/services/recommendations', [\App\Studio\Http\MarketFitController::class, 'record']);
Route::get('/api/v1/offerings', [\App\Studio\Http\PartnerOfferingController::class, 'index']);
Route::get('/admin/offerings', [\App\Studio\Admin\OfferingAdminController::class, 'index']);
Route::post('/admin/offerings/draft', [\App\Studio\Admin\OfferingAdminController::class, 'saveDraft']);
Route::post('/admin/offerings/publish', [\App\Studio\Admin\OfferingAdminController::class, 'publish']);
$mandateRouteSlugs = array_column(\App\Studio\Catalog\MandateCatalog::data()['mandates'], 'id');
$mandateRoutePattern = implode('|', array_map(static fn (string $slug): string => preg_quote($slug, '/'), $mandateRouteSlugs));
Route::get('/services/{slug}', [\App\Studio\Http\ServiceMandateController::class, 'show'])->where('slug', $mandateRoutePattern);

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
