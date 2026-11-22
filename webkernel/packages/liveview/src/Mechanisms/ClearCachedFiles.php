<?php

namespace Webkernel\Liveview\Mechanisms;

class ClearCachedFiles extends Mechanism
{
    function boot()
    {
        // Hook into Webkernel's view and optimization clear commands to clear component view caches.
        $eventCommands = [
            'view:clear',
            'optimize:clear',
        ];

        if ((PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg')) {
            app('events')->listen(\Webkernel\Console\Events\CommandFinished::class, function ($event) use ($eventCommands) {
                if (in_array($event->command, $eventCommands) && $event->exitCode === 0) {
                    app('liveview.compiler')->clearCompiled($event->output);
                }
            });
        }
    }
}
