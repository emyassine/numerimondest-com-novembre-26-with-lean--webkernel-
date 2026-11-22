<?php

namespace Webkernel\Platform\Http\Middleware;

use Webkernel\HttpFoundation\Request as HttpRequest;
use Webkernel\Route\Middleware\RouteMiddleware;
use Webkernel\Route\Request;

final class RequireValidSignature implements RouteMiddleware
{
    public function handle(Request $request, callable $next): mixed
    {
        abort_unless(HttpRequest::capture()->hasValidSignature(absolute: false), 403);

        return $next();
    }
}
