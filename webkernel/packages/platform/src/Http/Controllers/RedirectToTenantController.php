<?php

namespace Webkernel\Platform\Http\Controllers;

use Webkernel\Platform\Facades\Platform;
use Webkernel\Platform\Panel;
use \Webkernel\HttpFoundation\RedirectResponse;

class RedirectToTenantController
{
    public function __invoke(): RedirectResponse
    {
        $panel = Platform::getCurrentOrDefaultPanel();
        $tenant = Platform::getUserDefaultTenant(Platform::auth()->user());

        if (! $tenant) {
            return $this->redirectToTenantRegistration($panel);
        }

        $url = $panel->getUrl($tenant);

        if (blank($url)) {
            abort(404);
        }

        return redirect($url);
    }

    protected function redirectToTenantRegistration(Panel $panel): RedirectResponse
    {
        if (! ($panel->hasTenantRegistration() && platform()->getTenantRegistrationPage()::canView())) {
            abort(404);
        }

        return redirect($panel->getTenantRegistrationUrl());
    }
}
