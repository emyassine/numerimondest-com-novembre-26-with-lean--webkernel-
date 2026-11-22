<?php declare(strict_types=1);

namespace Webkernel\PackageAssets\Providers;

use Webkernel\ServiceProvider;

final class PackageAssetsProvider extends ServiceProvider
{
    public const array ROUTES = [__DIR__.'/../../routes.php'];
}
