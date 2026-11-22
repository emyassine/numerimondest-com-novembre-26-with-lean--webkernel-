<?php declare(strict_types=1);

namespace Webkernel\SimpleSite\Providers;

use Webkernel\ServiceProvider;

final class SimpleSiteProvider extends ServiceProvider
{
    public const array CONFIG = [__DIR__.'/../../config/simple-site.php'];
    public const array ROUTES = [__DIR__.'/../../routes.php'];
}
