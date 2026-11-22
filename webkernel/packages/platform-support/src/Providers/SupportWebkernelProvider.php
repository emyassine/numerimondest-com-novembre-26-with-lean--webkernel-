<?php

namespace Webkernel\Platform\Support\Providers;

use Webkernel\ServiceProvider;

final class SupportWebkernelProvider extends ServiceProvider
{
    public const array PUBLIC_ASSETS = [__DIR__ . '/../../dist'];
}
