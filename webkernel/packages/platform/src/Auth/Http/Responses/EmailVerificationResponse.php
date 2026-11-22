<?php

namespace Webkernel\Platform\Auth\Http\Responses;

use Webkernel\Platform\Auth\Http\Responses\Contracts\EmailVerificationResponse as Responsable;
use Webkernel\Platform\Facades\Platform;
use \Webkernel\HttpFoundation\RedirectResponse;
use Webkernel\Liveview\Features\SupportRedirects\Redirector;

class EmailVerificationResponse implements Responsable
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        return redirect()->intended(Platform::getUrl());
    }
}
