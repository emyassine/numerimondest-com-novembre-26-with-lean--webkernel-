<?php

namespace Webkernel\Platform\Forms\Components\RichEditor;

use Webkernel\Platform\Support\Colors\Color;
use Webkernel\Utilities\Arr;
use Webkernel\Utilities\Str;

class TextColor
{
    public function __construct(
        protected ?string $label = null,
        protected ?string $color = null,
        protected ?string $darkColor = null,
    ) {}

    public static function make(?string $label = null, ?string $color = null, ?string $darkColor = null): static
    {
        return app(static::class, [
            'label' => $label,
            'color' => $color,
            'darkColor' => $darkColor,
        ]);
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getSafeLabelHtml(): string
    {
        return e($this->getLabel());
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function getDarkColor(): ?string
    {
        return $this->darkColor ?? $this->getColor();
    }

    /**
     * @return array<string, TextColor>
     */
    public static function getDefaults(): array
    {
        $translations = __('webkernel-forms::components.rich_editor.actions.text_color.modal.form.color.options');

        return Arr::mapWithKeys(
            Color::all(),
            static fn (array $color, string $name): array => [$name => TextColor::make(
                is_array($translations) && array_key_exists($name, $translations)
                    ? $translations[$name]
                    : Str::ucwords($name),
                $color['600'],
                $color['400'] ?? null,
            )],
        );
    }
}
