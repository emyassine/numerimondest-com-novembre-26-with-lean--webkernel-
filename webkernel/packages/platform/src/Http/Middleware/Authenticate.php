<?php

namespace Webkernel\Platform\Http\Middleware;

use Webkernel\Platform\Facades\Platform;
use Webkernel\Platform\Models\Contracts\PlatformUser;
use \Webkernel\Accounts\Middleware\Authenticate as Middleware;
use Webkernel\ORM\Model;

class Authenticate extends Middleware
{
    /**
     * @param  array<string>  $guards
     */
    protected function authenticate($request, array $guards): void
    {
        $guard = Platform::auth();

        if (! $guard->check()) {
            $this->unauthenticated($request, $guards);

            return; /** @phpstan-ignore-line */
        }

        $this->auth->shouldUse(Platform::getAuthGuard());

        /** @var Model $user */
        $user = $guard->user();

        $panel = Platform::getCurrentOrDefaultPanel();

        // Security: If the user model does not implement `PlatformUser`,
        // access is only allowed in local environments. In production,
        // implement `PlatformUser` with `canAccessPanel()`.
        abort_if(
            $user instanceof PlatformUser ?
                (! $user->canAccessPanel($panel)) :
                (config('app.env') !== 'local'),
            403,
        );
    }

    protected function redirectTo($request): ?string
    {
        return Platform::getLoginUrl();
    }
}
