<?php

namespace Webkernel\Liveview\Features\SupportFileUploads;

use function Webkernel\Liveview\on;
use Webkernel\Liveview\ComponentHook;
use Webkernel\Route\Route;
use Webkernel\Liveview\Mechanisms\HandleRequests\EndpointResolver;
use Webkernel\Liveview\Facades\GenerateSignedUploadUrlFacade;

class SupportFileUploads extends ComponentHook
{
    static function provide()
    {
        if (app()->runningUnitTests()) {
            // Don't actually generate S3 signedUrls during testing.
            GenerateSignedUploadUrlFacade::swap(new class extends GenerateSignedUploadUrl {
                public function forS3($file, $visibility = '') { return []; }
            });
        }

        app('liveview')->propertySynthesizer([
            FileUploadSynth::class,
        ]);

        on('call', function ($component, $method, $params, $componentContext, $earlyReturn) {
            if ($method === '_startUpload') {
                if (! method_exists($component, $method)) {
                    throw new MissingFileUploadsTraitException($component);
                }
            }
        });

        Route::post(EndpointResolver::uploadPath(), [FileUploadController::class, 'handle'])
            ->name('liveview.upload-file');

        Route::get(EndpointResolver::previewPath(), [FilePreviewController::class, 'handle'])
            ->name('liveview.preview-file');
    }
}
