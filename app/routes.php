<?php declare(strict_types=1);

use Webkernel\Route\Route;

Route::get('/manifest.webmanifest', [\App\Studio\PwaController::class, 'manifest']);
Route::get('/service-worker.js', [\App\Studio\PwaController::class, 'serviceWorker']);

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
