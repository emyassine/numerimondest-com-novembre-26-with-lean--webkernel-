<?php

namespace Webkernel\Platform\Http\Middleware;

use Webkernel\Platform\Facades\Platform;
use Webkernel\Route\Middleware\ParameterizedRouteMiddleware;
use Webkernel\Route\Request;

final class SetResourceConfiguration implements ParameterizedRouteMiddleware
{
    public function handle(Request $request, callable $next, string ...$parameters): mixed
    {
        $key = $parameters[0] ?? '';
        if ($key === '' || count($parameters) !== 1) {
            abort(404);
        }

        $previousKey = Platform::getCurrentResourceConfigurationKey();
        Platform::setCurrentResourceConfigurationKey($key);

        try {
            return $next();
        } finally {
            Platform::setCurrentResourceConfigurationKey($previousKey);
        }
    }
}
