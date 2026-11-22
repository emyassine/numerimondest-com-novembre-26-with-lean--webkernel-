<?php

namespace Webkernel\Platform\Support\Commands;

use Webkernel\Platform\Support\Events\PlatformUpgraded;
use \Webkernel\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'platform:upgrade')]
class UpgradeCommand extends Command
{
    protected $description = 'Upgrade Platform to the latest version';

    protected $name = 'platform:upgrade';

    public function handle(): int
    {
        foreach ([
            AssetsCommand::class,
            'config:clear',
            'route:clear',
            'view:clear',
        ] as $command) {
            $this->call($command);
        }

        PlatformUpgraded::dispatch();

        $this->components->info('Successfully upgraded!');

        return static::SUCCESS;
    }
}
