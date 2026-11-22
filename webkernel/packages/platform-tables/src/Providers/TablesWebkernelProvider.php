<?php

namespace Webkernel\Platform\Tables\Providers;

use Webkernel\ServiceProvider;

final class TablesWebkernelProvider extends ServiceProvider
{
    public const array PUBLIC_ASSETS = [__DIR__ . '/../../dist'];
}
