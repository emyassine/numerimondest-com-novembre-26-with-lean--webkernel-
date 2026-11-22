<?php

namespace Webkernel\Platform\Auth\MultiFactor\Http\Middleware;

use Closure;
use Webkernel\Platform\Auth\MultiFactor\MultiFactorChallenge;
use Webkernel\Platform\Facades\Platform;
use \Webkernel\HttpFoundation\Request;

class EnsureMultiFactorAuthenticationIsEnabled
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (MultiFactorChallenge::make()->hasEnabledProviders(Platform::auth()->user())) {
            return $next($request);
        }

        return redirect()->guest(Platform::getSetUpRequiredMultiFactorAuthenticationUrl());
    }
}
