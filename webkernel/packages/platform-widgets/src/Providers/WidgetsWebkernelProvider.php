<?php

namespace Webkernel\Platform\Widgets\Providers;

use Webkernel\ServiceProvider;

final class WidgetsWebkernelProvider extends ServiceProvider
{
    public const array PUBLIC_ASSETS = [__DIR__ . '/../../dist'];
}
