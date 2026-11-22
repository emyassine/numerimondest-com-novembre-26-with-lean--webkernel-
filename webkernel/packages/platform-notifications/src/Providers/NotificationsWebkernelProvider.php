<?php

namespace Webkernel\Platform\Notifications\Providers;

use Webkernel\ServiceProvider;

final class NotificationsWebkernelProvider extends ServiceProvider
{
    public const array PUBLIC_ASSETS = [__DIR__ . '/../../dist'];
}
