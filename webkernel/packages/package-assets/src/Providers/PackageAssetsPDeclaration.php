<?php declare(strict_types=1);

namespace Webkernel\PackageAssets\Providers;

use Webkernel\ServiceProvider\PackageMetadata;

final class PackageAssetsPDeclaration extends PackageMetadata
{
    protected const array METADATA = ['prefix' => 'webkernel', 'catalog' => []];
}
