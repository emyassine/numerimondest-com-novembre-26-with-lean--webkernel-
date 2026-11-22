<?php declare(strict_types=1);

namespace App\Providers;

use Webkernel\ServiceProvider\PackageMetadata;

final class AppPDeclaration extends PackageMetadata
{
    protected const array METADATA = [
        'prefix' => 'app',
        'catalog' => [],
    ];
}
