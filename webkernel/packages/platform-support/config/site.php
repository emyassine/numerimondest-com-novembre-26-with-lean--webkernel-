<?php declare(strict_types=1);

return [
    'boot' => [\Webkernel\Platform\Support\Webkernel\AttributeBagMacros::class.'::register'],
    'components' => [
        'webkernel-platform' => [__DIR__.'/../resources/views/components'],
    ],
    'public_assets' => [__DIR__.'/../dist'],
];
