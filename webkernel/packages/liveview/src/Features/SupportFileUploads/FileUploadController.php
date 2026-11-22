<?php

namespace Webkernel\Liveview\Features\SupportFileUploads;

use \Webkernel\Route\Controllers\HasMiddleware;
use \Webkernel\Route\Controllers\Middleware;
use \Webkernel\Validation\Validation as Validator;
use \Webkernel\Liveview\Providers\WebMiddleware;

class FileUploadController implements HasMiddleware
{
    public static array $defaultMiddleware = [WebMiddleware::class];

    public static function middleware()
    {
        $middleware = (array) FileUploadConfiguration::middleware();

        // Prepend the default middleware to the middleware array if it's not already present...
        foreach (array_reverse(static::$defaultMiddleware) as $defaultMiddleware) {
            if (! in_array($defaultMiddleware, $middleware)) {
                array_unshift($middleware, $defaultMiddleware);
            }
        }

        return array_map(fn ($middleware) => new Middleware($middleware), $middleware);
    }

    public function handle()
    {
        abort_unless(request()->hasValidRelativeSignature(), 401);

        $disk = FileUploadConfiguration::disk();

        $filePaths = $this->validateAndStore(request('files'), $disk);

        return ['paths' => $filePaths];
    }

    public function validateAndStore($files, $disk)
    {
        Validator::make(['files' => $files], [
            'files.*' => FileUploadConfiguration::rules()
        ])->validate();

        $fileHashPaths = collect($files)->map(function ($file) use ($disk) {
            return FileUploadConfiguration::storeTemporaryFile($file, $disk);
        });

        // Strip out the temporary upload directory from the paths and sign them.
        return $fileHashPaths->map(function ($path) {
            $stripped = str_replace(FileUploadConfiguration::path('/'), '', $path);

            return TemporaryUploadedFile::signPath($stripped);
        });
    }
}
