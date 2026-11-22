<?php

namespace Webkernel\Liveview\Features\SupportFileUploads;

use \Webkernel\Route\Controllers\HasMiddleware;
use \Webkernel\Route\Controllers\Middleware;
use Webkernel\Liveview\Drawer\Utils;
use \Webkernel\Liveview\Providers\WebMiddleware;

class FilePreviewController implements HasMiddleware
{
    public static array $middleware = [WebMiddleware::class];

    public static function middleware()
    {
        return array_map(fn ($middleware) => new Middleware($middleware), static::$middleware);
    }

    public function handle($filename)
    {
        abort_unless(request()->hasValidRelativeSignature(), 401);

        return Utils::pretendPreviewResponseIsPreviewFile($filename);
    }
}
