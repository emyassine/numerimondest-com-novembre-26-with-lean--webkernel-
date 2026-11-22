<?php

namespace Webkernel\Platform\Auth\Http\Controllers;

use Webkernel\Platform\Auth\Http\Responses\Contracts\LogoutResponse;
use Webkernel\Platform\Facades\Platform;

class LogoutController
{
    public function __invoke(): LogoutResponse
    {
        Platform::auth()->logout();

        session()->invalidate();
        session()->regenerateToken();

        return app(LogoutResponse::class);
    }
}
