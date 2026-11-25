<?php declare(strict_types=1);

use App\Studio\Admin\AdminController;
use App\Studio\Http\MarketFitController;
use App\Studio\Http\PartnerOfferingController;
use App\Studio\Http\PwaController;
use App\Studio\Http\ServiceInquiryController;
use App\Studio\Http\ServiceMandateController;
use App\Studio\Http\ShortlistController;
use Webkernel\Route\Route;

$api_version = (int) 1;

/*------------------------------------------*
// PWA
/*------------------------------------------*/
Route::get('/manifest.webmanifest', [PwaController::class, 'manifest']);
Route::get('/service-worker.js', [PwaController::class, 'serviceWorker']);

/*------------------------------------------*
// Service Inquiry, Shortlist & Recommendations
/*------------------------------------------*/
Route::post('/service-inquiry/select', [ServiceInquiryController::class, 'select']);
Route::post('/service-inquiry/project/remove', [ServiceInquiryController::class, 'removeProject']);
Route::post('/service-inquiry', [ServiceInquiryController::class, 'submit']);
Route::post('/shortlist/toggle', [ShortlistController::class, 'toggle']);
Route::post('/services/recommendations', [MarketFitController::class, 'record']);

/*------------------------------------------*
// Partner Offerings API
/*------------------------------------------*/
Route::get("/api/v{$api_version}/offerings", [PartnerOfferingController::class, 'index']);
Route::get("/api/v{$api_version}/offerings/{id}", [PartnerOfferingController::class, 'show']);
Route::post("/api/v{$api_version}/quotes", [PartnerOfferingController::class, 'quote']);

/*------------------------------------------*
// Private Administration (local and development environments only)
/*------------------------------------------*/
if (!is_env('prod')) {
    Route::get('/admin', [AdminController::class, 'databaseManager']);
    Route::get('/admin/login', [AdminController::class, 'loginForm']);
    Route::post('/admin/login', [AdminController::class, 'login']);
    Route::post('/admin/logout', [AdminController::class, 'logout']);
    Route::get('/admin/offerings', [AdminController::class, 'index']);
    Route::post('/admin/offerings/preview', [AdminController::class, 'preview']);
    Route::post('/admin/offerings/draft', [AdminController::class, 'saveDraft']);
    Route::post('/admin/offerings/publish', [AdminController::class, 'publish']);
}

/*------------------------------------------*
// Service Mandates
/*------------------------------------------*/
Route::get('/services/{slug}', [ServiceMandateController::class, 'show']);
