<?php

namespace Webkernel\Platform\Http\Middleware;

use Closure;
use Webkernel\Platform\Events\ServingPlatform;
use \Webkernel\HttpFoundation\Request;

class DispatchServingPlatformEvent
{
    public function handle(Request $request, Closure $next): mixed
    {
        ServingPlatform::dispatch();

        return $next($request);
    }
}
