<?php

namespace Webkernel\Liveview\Features\SupportConsoleCommands;

use \Webkernel\Console\Application as Artisan;
use Webkernel\Liveview\ComponentHook;

class SupportConsoleCommands extends ComponentHook
{
    static function provide()
    {
        if (! (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg')) return;

        static::commands([
            Commands\MakeCommand::class,         // make:livewire
            Commands\LivewireMakeCommand::class, // livewire:make (alias)
            Commands\ConvertCommand::class,      // livewire:convert
            Commands\FormCommand::class,         // livewire:form
            Commands\AttributeCommand::class,    // livewire:attribute
            Commands\LayoutCommand::class,       // livewire:layout
            Commands\StubsCommand::class,        // livewire:stubs
            Commands\S3CleanupCommand::class,    // livewire:configure-s3-upload-cleanup
            Commands\PublishCommand::class,      // livewire:publish
            Commands\ConfigCommand::class,       // livewire:config
        ]);
    }

    static function commands($commands)
    {
        $commands = is_array($commands) ? $commands : func_get_args();

        // Filter out null values
        $commands = array_filter($commands);

        Artisan::starting(fn(Artisan $artisan) => $artisan->resolveCommands($commands));
    }
}
