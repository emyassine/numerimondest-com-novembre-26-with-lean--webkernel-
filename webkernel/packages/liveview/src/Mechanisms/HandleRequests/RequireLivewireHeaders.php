<?php

namespace Webkernel\Liveview\Mechanisms\HandleRequests;

use Closure;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Webkernel\Route\Middleware\RouteMiddleware;
use Webkernel\Route\Request;

class RequireLivewireHeaders implements RouteMiddleware
{
    public function handle(Request $request, callable $next): mixed
    {
        // Reject requests missing required headers. The legitimate Livewire
        // JS client always sends both X-Livewire and Content-Type: application/json.
        // Their absence indicates the request did not come from Livewire.
        // Return 404 to avoid confirming the endpoint exists to scanners.
        if (! $request->hasHeader('X-Livewire') || ! $request->isJson()) {
            throw new NotFoundHttpException();
        }

        return $next();
    }
}
