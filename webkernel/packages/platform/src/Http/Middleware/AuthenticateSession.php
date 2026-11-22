<?php

namespace Webkernel\Platform\Http\Middleware;

use Webkernel\Platform\Facades\Platform;
use \Webkernel\Session\Middleware\AuthenticateSession as Middleware;

class AuthenticateSession extends Middleware
{
    protected function redirectTo($request): ?string
    {
        return Platform::getLoginUrl();
    }
}
