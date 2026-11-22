<?php declare(strict_types=1);

namespace Webkernel\SimpleSite\Providers;

use Webkernel\ServiceProvider\PackageMetadata;

final class SimpleSitePDeclaration extends PackageMetadata
{
    protected const array METADATA = [
        'prefix' => 'simple_site',
        'catalog' => [
            'title' => ['en' => 'Simple Site'],
            'summary' => ['en' => 'File-based websites with automatic routes and SEO discovery endpoints.'],
            'category' => ['en' => 'Websites'],
            'visibility' => 'public',
            'type' => 'platform',
        ],
    ];
}
