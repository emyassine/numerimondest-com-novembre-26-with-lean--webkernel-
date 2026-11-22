<?php

namespace Webkernel\Platform\Schemas\Providers;

use Webkernel\ServiceProvider;

final class SchemasWebkernelProvider extends ServiceProvider
{
    public const array PUBLIC_ASSETS = [__DIR__ . '/../../dist'];
}
