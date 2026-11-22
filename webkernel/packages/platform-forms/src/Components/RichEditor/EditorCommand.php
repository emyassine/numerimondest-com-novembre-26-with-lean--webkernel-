<?php

namespace Webkernel\Platform\Forms\Components\RichEditor;

use Webkernel\Utilities\Contracts\Arrayable;

class EditorCommand implements Arrayable
{
    /**
     * @param  array<mixed>  $arguments
     */
    public function __construct(
        public readonly string $name,
        public readonly array $arguments = [],
    ) {}

    /**
     * @param  array<mixed>  $arguments
     */
    public static function make(string $name, array $arguments = []): static
    {
        return app(static::class, ['name' => $name, 'arguments' => $arguments]);
    }

    /**
     * @return array{name: string, arguments: array<mixed>}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'arguments' => $this->arguments,
        ];
    }
}
