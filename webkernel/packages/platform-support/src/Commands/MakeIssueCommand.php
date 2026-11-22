<?php

namespace Webkernel\Platform\Support\Commands;

use Webkernel\Platform\Support\Commands\Concerns\CanOpenUrlInBrowser;
use \Webkernel\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'make:platform-issue', aliases: [
    'platform:issue',
    'platform:make-issue',
])]
class MakeIssueCommand extends Command
{
    use CanOpenUrlInBrowser;

    protected $description = 'Opens Webkernel Platform product and support information.';

    protected $name = 'make:platform-issue';

    /**
     * @var array<string>
     */
    protected $aliases = [
        'platform:issue',
        'platform:make-issue',
    ];

    public function handle(): void
    {
        $this->openUrlInBrowser('https://www.webkernelphp.com');
    }
}
