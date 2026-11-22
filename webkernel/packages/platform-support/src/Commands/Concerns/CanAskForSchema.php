<?php

namespace Webkernel\Platform\Support\Commands\Concerns;


use function console;
use function Webkernel\Platform\Support\discover_app_classes;
trait CanAskForSchema
{
    /**
     * @return ?class-string
     */
    protected function askForSchema(string $intialQuestion, string $question, string $questionPlaceholder): ?string
    {
        if (! console()->confirm(
            label: $intialQuestion,
            default: false,
        )) {
            return null;
        }

        $schemaFqns = array_filter(
            discover_app_classes(),
            fn (string $schemaFqn): bool => method_exists($schemaFqn, 'configure'),
        );

        return console()->suggest(
            label: $question,
            options: function (?string $search) use ($schemaFqns): array {
                if (blank($search)) {
                    return $schemaFqns;
                }

                $search = str($search)->trim()->replace(['\\', '/'], '');

                return array_filter($schemaFqns, fn (string $schemaFqn): bool => str($schemaFqn)->replace(['\\', '/'], '')->contains($search, ignoreCase: true));
            },
            placeholder: $questionPlaceholder,
            hint: 'Please provide the fully-qualified class name.',
        );
    }
}
