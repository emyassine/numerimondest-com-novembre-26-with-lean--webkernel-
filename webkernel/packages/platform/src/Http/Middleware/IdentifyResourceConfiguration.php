<?php

namespace Webkernel\Platform\Http\Middleware;

use Closure;
use Webkernel\Platform\Facades\Platform;
use \Webkernel\HttpFoundation\Request;

class IdentifyResourceConfiguration
{
    public function handle(Request $request, Closure $next, string $key): mixed
    {
        Platform::setCurrentResourceConfigurationKey($key);

        return $next($request);
    }
}
