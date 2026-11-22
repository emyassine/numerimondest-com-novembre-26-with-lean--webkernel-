<?php

namespace Webkernel\Platform\Support\Commands\Concerns;


use function console;
use Webkernel\Platform\Support\Facades\PlatformCli;
use Webkernel\Utilities\Str;

trait CanAskForLivewireComponentLocation
{
    protected function getLivewireComponentViewName(string $namespace, string $relativeComponentClass): string
    {
        $namespace = (string) str($namespace)
            ->trim('\\')
            ->prepend('\\')
            ->append('\\');

        $relativeNamespace = str($namespace)->contains('\\Webkernel\Liveview\\')
            ? str($namespace)->afterLast('\\Webkernel\Liveview\\')->trim('\\')
            : str('');

        return (string) $relativeNamespace
            ->whenNotEmpty(static fn ($relativeNamespace) => $relativeNamespace->append('\\'))
            ->append($relativeComponentClass)
            ->prepend('Webkernel\Liveview\\')
            ->replace('\\', '/')
            ->explode('/')
            ->map(Str::kebab(...))
            ->implode('.');
    }

    /**
     * @return array{
     *     0: string,
     *     1: string,
     *     2: ?string,
     * }
     */
    protected function askForLivewireComponentLocation(string $question = 'Where would you like to create the Livewire component?'): array
    {
        $locations = PlatformCli::getLivewireComponentLocations();

        if (blank($locations)) {
            return [
                app()->getNamespace() . 'Livewire',
                app_path('Livewire'),
                '',
            ];
        }

        $options = [
            '' => app()->getNamespace() . 'Livewire',
            ...array_combine(
                array_keys($locations),
                array_keys($locations),
            ),
        ];

        $namespace = console()->select(
            label: $question,
            options: $options,
            default: $this->input->isInteractive() ? null : '',
        );

        if (blank($namespace)) {
            return [
                app()->getNamespace() . 'Livewire',
                app_path('Livewire'),
                '',
            ];
        }

        return [
            $namespace,
            $locations[$namespace]['path'],
            $locations[$namespace]['viewNamespace'] ?? null,
        ];
    }
}
