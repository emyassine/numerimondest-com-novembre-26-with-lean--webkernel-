<?php

namespace Webkernel\Platform\Http\Middleware;

use Webkernel\HttpFoundation\Request as HttpRequest;
use Webkernel\Route\Middleware\RouteMiddleware;
use Webkernel\Route\Request;

final class AuthenticatePlatformActions implements RouteMiddleware
{
    public function handle(Request $request, callable $next): mixed
    {
        $httpRequest = HttpRequest::capture();
        $guard = $httpRequest->hasValidSignature(absolute: false)
            ? $httpRequest->query('authGuard')
            : null;

        abort_unless(auth(is_string($guard) ? $guard : null)->check(), 401);

        return $next();
    }
}
