<?php

namespace Webkernel\Platform\Support\Commands;

use Closure;
use Webkernel\Platform\Support\Commands\Concerns\CanCachePanelComponents;
use \Webkernel\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'platform:optimize-clear')]
class OptimizeClearCommand extends Command
{
    use CanCachePanelComponents;

    protected $description = 'Remove cached panel component metadata';

    protected $name = 'platform:optimize-clear';

    public function handle(): int
    {
        $this->components->info('Clearing cached panel component metadata.');

        $tasks = collect();

        if ($this->canCachePanelComponents()) {
            $tasks->put(
                'Clearing cached components',
                fn (): bool => $this->callSilent('platform:clear-cached-components') === static::SUCCESS,
            );
        }

        $tasks->each(fn (Closure $task, string $description) => $this->components->task($description, $task));

        $this->newLine();

        return static::SUCCESS;
    }
}
