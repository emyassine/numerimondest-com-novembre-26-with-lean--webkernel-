<?php

namespace Webkernel\Platform\Support\Commands;

use Closure;
use Webkernel\Platform\Support\Commands\Concerns\CanCachePanelComponents;
use \Webkernel\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'platform:optimize')]
class OptimizeCommand extends Command
{
    use CanCachePanelComponents;

    protected $description = 'Cache panel component metadata to improve startup performance';

    protected $name = 'platform:optimize';

    public function handle(): int
    {
        $this->components->info('Caching panel component metadata.');

        $tasks = collect();

        if ($this->canCachePanelComponents()) {
            $tasks->put(
                'Caching components',
                fn (): bool => $this->callSilent('platform:cache-components') === static::SUCCESS
            );
        }

        $tasks->each(fn (Closure $task, string $description) => $this->components->task($description, $task));

        $this->newLine();

        return static::SUCCESS;
    }
}
