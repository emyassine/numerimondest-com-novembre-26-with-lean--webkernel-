<?php

namespace Webkernel\Platform\Commands;

use Webkernel\Platform\Facades\Platform;
use \Webkernel\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'platform:cache-components')]
class CacheComponentsCommand extends Command
{
    protected $description = 'Cache all components';

    protected $name = 'platform:cache-components';

    public function handle(): int
    {
        $this->info('Caching registered components...');

        foreach (Platform::getPanels() as $panel) {
            $panel->cacheComponents();
        }

        $this->info('All done!');

        return static::SUCCESS;
    }
}
