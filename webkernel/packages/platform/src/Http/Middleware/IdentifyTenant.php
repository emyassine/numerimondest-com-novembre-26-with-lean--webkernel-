<?php

namespace Webkernel\Platform\Http\Middleware;

use Webkernel\Platform\Facades\Platform;
use Webkernel\Platform\Models\Contracts\HasTenants;
use Webkernel\ORM\Model;
use Webkernel\ORM\ModelNotFoundException;
use Webkernel\Route\Middleware\RouteMiddleware;
use Webkernel\Route\Request;

class IdentifyTenant implements RouteMiddleware
{
    public function handle(Request $request, callable $next): mixed
    {
        // Security: Tenant identification occurs in this middleware. Global
        // scopes for tenant isolation are only active AFTER this runs.
        // Queries in earlier middleware or service providers will not
        // be tenant-scoped. Ensure tenant-aware middleware uses
        // `isPersistent: true` for Livewire AJAX enforcement.

        $panel = Platform::getCurrentOrDefaultPanel();

        if (! $panel->hasTenancy()) {
            return $next();
        }

        $route = $request->route();

        if ($route === null || ! array_key_exists('tenant', $route->parameters())) {
            return $next();
        }

        $tenantKey = $route->parameter('tenant');

        if (! is_string($tenantKey) || $tenantKey === '') {
            abort(404);
        }

        /** @var Model $user */
        $user = $panel->auth()->user();

        if (! $user instanceof HasTenants) {
            abort(404);
        }

        try {
            $tenant = $panel->getTenant($tenantKey);
        } catch (ModelNotFoundException) {
            abort(404);
        }

        if (! $user->canAccessTenant($tenant)) {
            abort(404);
        }

        Platform::setTenant($tenant);

        return $next();
    }
}
