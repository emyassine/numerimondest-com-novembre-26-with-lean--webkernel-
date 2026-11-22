<?php

namespace Webkernel\Platform\Notifications\Http\Middleware;

use Closure;
use Webkernel\Platform\Notifications\NotificationMailbox;
use \Webkernel\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class InitializeNotificationMailbox
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasSession()) {
            app(NotificationMailbox::class)->initializeScope();
        }

        $response = $next($request);

        // An invalidation may have cleared the marker. Persist the final scope
        // before `StartSession` saves, even on pages without a notification host.
        if ($request->hasSession()) {
            app(NotificationMailbox::class)->initializeScope();
        }

        return $response;
    }
}
