<?php

namespace Webkernel\Platform\Webkernel;

use Webkernel\Platform\Http\Middleware\RequireValidSignature;
use Webkernel\Platform\Http\Middleware\AuthenticatePlatformActions;
use Webkernel\Platform\Http\Middleware\SetResourceConfiguration;
use Webkernel\Platform\Http\Middleware\ThrottlePlatformAuth;
use Webkernel\Route\Route;

final class PlatformRouteMiddleware
{
    public static function register(): void
    {
        Route::middlewareAlias('signed', RequireValidSignature::class);
        Route::middlewareAlias('throttle', ThrottlePlatformAuth::class);
        Route::middlewareAlias('platform.actions', AuthenticatePlatformActions::class);
        Route::middlewareAlias('resource-configuration', SetResourceConfiguration::class);
    }
}
