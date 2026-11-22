<?php declare(strict_types=1);

namespace Webkernel\HttpErrorPages\Providers;

use Webkernel\ServiceProvider\PackageMetadata;

final class HttpErrorPagesPDeclaration extends PackageMetadata
{
    protected const array METADATA = [
        'prefix' => 'http_error_pages',
        'catalog' => [
            'title' => ['en' => 'HTTP Error Pages'],
            'summary' => ['en' => 'Localized and configurable HTTP error pages.'],
            'category' => ['en' => 'HTTP'],
            'visibility' => 'public',
            'type' => 'platform',
        ],
    ];
}
