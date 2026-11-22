<?php declare(strict_types=1);

namespace Webkernel\Runtime\Providers;

use Webkernel\ServiceProvider\PackageMetadata;

final class HttpRuntimePDeclaration extends PackageMetadata
{
    protected const array METADATA = ['prefix' => 'webkernel', 'catalog' => []];
}
