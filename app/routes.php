<?php declare(strict_types=1);

$api_version = (int) 1;

use Webkernel\Route\Route;

/*------------------------------------------*
// PWA
/*------------------------------------------*/
Route::get('/manifest.webmanifest', [\App\Studio\Http\PwaController::class, 'manifest']);
Route::get('/service-worker.js', [\App\Studio\Http\PwaController::class, 'serviceWorker']);

/*------------------------------------------*
// Service Inquiry, Shortlist & Recommendations
/*------------------------------------------*/
Route::post('/service-inquiry/select', [\App\Studio\Http\ServiceInquiryController::class, 'select']);
Route::post('/service-inquiry/project/remove', [\App\Studio\Http\ServiceInquiryController::class, 'removeProject']);
Route::post('/service-inquiry', [\App\Studio\Http\ServiceInquiryController::class, 'submit']);
Route::post('/shortlist/toggle', [\App\Studio\Http\ShortlistController::class, 'toggle']);
Route::post('/services/recommendations', [\App\Studio\Http\MarketFitController::class, 'record']);

/*------------------------------------------*
// Partner Offerings API
/*------------------------------------------*/
Route::get("/api/v{$api_version}/offerings", [\App\Studio\Http\PartnerOfferingController::class, 'index']);
Route::get("/api/v{$api_version}/offerings/{id}", [\App\Studio\Http\PartnerOfferingController::class, 'show']);
Route::post("/api/v{$api_version}/quotes", [\App\Studio\Http\PartnerOfferingController::class, 'quote']);

/*------------------------------------------*
// Admin Offerings
/*------------------------------------------*/
Route::get('/admin/offerings', [\App\Studio\Admin\OfferingAdminController::class, 'index']);
Route::post('/admin/offerings/preview', [\App\Studio\Admin\OfferingAdminController::class, 'preview']);
Route::post('/admin/offerings/draft', [\App\Studio\Admin\OfferingAdminController::class, 'saveDraft']);
Route::post('/admin/offerings/publish', [\App\Studio\Admin\OfferingAdminController::class, 'publish']);

/*------------------------------------------*
// Service Mandates
/*------------------------------------------*/
Route::get('/services/{slug}', [\App\Studio\Http\ServiceMandateController::class, 'show']);
