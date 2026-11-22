<?php

namespace Webkernel\Platform\Http\Middleware;

use InvalidArgumentException;
use Webkernel\Cache\ConfiguredRateLimiter;
use Webkernel\Route\Middleware\ParameterizedRouteMiddleware;
use Webkernel\Route\Request;

final class ThrottlePlatformAuth implements ParameterizedRouteMiddleware
{
    private const int MAX_ATTEMPTS = 20;
    private const int DECAY_SECONDS = 60;

    public function handle(Request $request, callable $next, string ...$parameters): mixed
    {
        if ($parameters === ['platform:auth']) {
            $maxAttempts = self::MAX_ATTEMPTS;
            $decaySeconds = self::DECAY_SECONDS;
        } elseif (
            count($parameters) === 2
            && ctype_digit($parameters[0])
            && ctype_digit($parameters[1])
            && (int) $parameters[0] > 0
            && (int) $parameters[1] > 0
        ) {
            $maxAttempts = (int) $parameters[0];
            $decaySeconds = (int) $parameters[1] * 60;
        } else {
            throw new InvalidArgumentException('Unknown Platform auth throttle profile.');
        }

        $routeName = \Webkernel\Route\Route::currentName() ?? '';
        $key = hash('sha256', $routeName."\0".($request->ip() ?? 'unknown'));
        $limiter = app(ConfiguredRateLimiter::class);

        if ($limiter->tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = max(1, $limiter->availableIn($key));
            header('Retry-After: '.$retryAfter);
            abort(429, 'Too many requests.');
        }

        $limiter->hit($key, $decaySeconds);

        return $next();
    }
}
