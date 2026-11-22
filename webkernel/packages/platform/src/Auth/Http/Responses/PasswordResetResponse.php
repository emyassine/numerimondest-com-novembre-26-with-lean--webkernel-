<?php

namespace Webkernel\Platform\Auth\Http\Responses;

use Webkernel\Platform\Auth\Http\Responses\Contracts\PasswordResetResponse as Responsable;
use Webkernel\Platform\Facades\Platform;
use \Webkernel\HttpFoundation\RedirectResponse;
use Webkernel\Liveview\Features\SupportRedirects\Redirector;

class PasswordResetResponse implements Responsable
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        return redirect()->to(
            Platform::hasLogin() ? Platform::getLoginUrl() : Platform::getUrl(),
        );
    }
}
