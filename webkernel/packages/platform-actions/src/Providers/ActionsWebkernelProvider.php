<?php

namespace Webkernel\Platform\Actions\Providers;

use Webkernel\ServiceProvider;

final class ActionsWebkernelProvider extends ServiceProvider
{
    public const array ROUTES = [__DIR__ . '/../../routes/web.php'];

    public const array PUBLIC_ASSETS = [__DIR__ . '/../../dist'];
}
