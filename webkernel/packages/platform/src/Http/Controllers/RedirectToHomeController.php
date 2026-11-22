<?php

namespace Webkernel\Platform\Http\Controllers;

use Webkernel\Platform\Facades\Platform;
use \Webkernel\HttpFoundation\RedirectResponse;

class RedirectToHomeController
{
    public function __invoke(): RedirectResponse
    {
        $panel = Platform::getCurrentOrDefaultPanel();

        $url = $panel->getRedirectUrl(Platform::getTenant());

        if (blank($url)) {
            abort(404);
        }

        return redirect($url);
    }
}
