<?php declare(strict_types=1);

return [
    'commands' => [
        \Webkernel\Commands\AssetsCommand::class,
        \Webkernel\Commands\BinCommand::class,
        \Webkernel\Commands\CompletionCommand::class,
        \Webkernel\Commands\DeployCommand::class,
        \Webkernel\Commands\MakeMigrationCommand::class,
        \Webkernel\Commands\RouteListCommand::class,
        \Webkernel\Commands\ServerCommand::class,
    ],
];
