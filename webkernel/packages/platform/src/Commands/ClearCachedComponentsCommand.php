<?php

namespace Webkernel\Platform\Commands;

use Webkernel\Platform\Facades\Platform;
use \Webkernel\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'platform:clear-cached-components')]
class ClearCachedComponentsCommand extends Command
{
    protected $description = 'Clear all cached components';

    protected $name = 'platform:clear-cached-components';

    public function handle(): int
    {
        $this->info('Clearing cached components...');

        foreach (Platform::getPanels() as $panel) {
            $panel->clearCachedComponents();
        }

        $this->info('All done!');

        return static::SUCCESS;
    }
}
