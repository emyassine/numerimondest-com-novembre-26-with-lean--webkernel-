<?php declare(strict_types=1);

namespace Webkernel\Accounts\Providers;

use Webkernel\ServiceProvider\PackageMetadata;

final class AccountsPDeclaration extends PackageMetadata
{
    protected const array METADATA = [
        'prefix' => 'accounts',
        'catalog' => [
            'title' => ['en' => 'Accounts'],
            'summary' => ['en' => 'Shared accounts and explicit permission checks.'],
            'category' => ['en' => 'Security'],
            'visibility' => 'public',
            'type' => 'library',
        ],
    ];
}
