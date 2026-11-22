<?php

namespace Webkernel\Platform\Support\Commands\Concerns;


use function console;
use Webkernel\Platform\Support\Facades\PlatformCli;

trait CanAskForComponentLocation
{
    /**
     * @return array{
     *     0: string,
     *     1: string,
     *     2: ?string,
     * }
     */
    protected function askForComponentLocation(string $path, string $question = 'Where would you like to create the component?'): array
    {
        $pathNamespace = (string) str($path)->replace('/', '\\');

        $locations = PlatformCli::getComponentLocations();

        if (blank($locations)) {
            return [
                app()->getNamespace() . "Webkernel\Platform\\{$pathNamespace}",
                app_path('Platform' . DIRECTORY_SEPARATOR . $path),
                '',
            ];
        }

        $options = [
            '' => app()->getNamespace() . "Webkernel\Platform\\{$pathNamespace}",
            ...array_map(
                fn (string $namespace): string => "{$namespace}\\{$pathNamespace}",
                array_combine(
                    array_keys($locations),
                    array_keys($locations),
                ),
            ),
        ];

        $namespace = console()->select(
            label: $question,
            options: $options,
            default: $this->input->isInteractive() ? null : '',
        );

        if (blank($namespace)) {
            return [
                app()->getNamespace() . "Webkernel\Platform\\{$pathNamespace}",
                app_path('Platform' . DIRECTORY_SEPARATOR . $path),
                '',
            ];
        }

        return [
            "{$namespace}\\{$pathNamespace}",
            $locations[$namespace]['path'] . '/' . $path,
            $locations[$namespace]['viewNamespace'] ?? null,
        ];
    }
}
