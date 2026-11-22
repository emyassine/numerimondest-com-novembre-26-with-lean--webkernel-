<?php

namespace Webkernel\Platform\Support\Commands;

use \Webkernel\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'platform:about')]
class AboutCommand extends Command
{
    protected $description = 'Display basic information about Platform packages that are installed';

    protected $name = 'platform:about';

    public function handle(): void
    {
        $this->call('about', [
            '--only' => 'platform',
        ]);
    }
}
