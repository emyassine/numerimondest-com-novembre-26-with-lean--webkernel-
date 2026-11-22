<?php

namespace Webkernel\Platform\Forms\Components\RichEditor\Actions;

use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Forms\Components\ColorPicker;
use Webkernel\Platform\Forms\Components\RichEditor;
use Webkernel\Platform\Forms\Components\RichEditor\EditorCommand;
use Webkernel\Platform\Forms\Components\RichEditor\TextColor;
use Webkernel\Platform\Forms\Components\Select;
use Webkernel\Platform\Support\Enums\Width;
use Webkernel\Utilities\Arr;

class TextColorAction
{
    public static function make(): Action
    {
        return Action::make('textColor')
            ->label(__('webkernel-forms::components.rich_editor.actions.text_color.label'))
            ->modalHeading(__('webkernel-forms::components.rich_editor.actions.text_color.modal.heading'))
            ->modalWidth(Width::Large)
            ->fillForm(fn (array $arguments, RichEditor $component): ?array => filled($arguments['color'] ?? null) ? [
                'color' => array_key_exists($arguments['color'], $options = $component->getTextColors()) ? $arguments['color'] : null,
                'customColor' => $component->hasCustomTextColors() && (! array_key_exists($arguments['color'], $options)) ? $arguments['color'] : null,
            ] : null)
            ->schema(function (RichEditor $component) {
                $options = Arr::mapWithKeys(
                    $component->getTextColors(),
                    fn (TextColor $color, string $name): array => [$name => <<<HTML
                        <div class="fi-fo-rich-editor-text-color-select-option">
                            <div class="fi-fo-rich-editor-text-color-select-option-preview" style="--color: {$color->getColor()}; --dark-color: {$color->getDarkColor()}"></div>

                            <div>{$color->getSafeLabelHtml()}</div>
                        </div>
                        HTML],
                );

                return [
                    Select::make('color')
                        ->label(__('webkernel-forms::components.rich_editor.actions.text_color.modal.form.color.label'))
                        ->options($options)
                        ->allowHtml()
                        ->native(false)
                        ->visible(filled($options)),
                    ColorPicker::make('customColor')
                        ->label(__('webkernel-forms::components.rich_editor.actions.text_color.modal.form.custom_color.label'))
                        ->visibleJs(<<<'JS'
                            ! $get('color')
                            JS)
                        ->visible($component->hasCustomTextColors()),
                ];
            })
            ->action(function (array $arguments, array $data, RichEditor $component): void {
                $isSingleCharacterSelection = ($arguments['editorSelection']['head'] ?? null) === ($arguments['editorSelection']['anchor'] ?? null);

                $color = $data['color'] ?? $data['customColor'] ?? null;

                if (blank($color)) {
                    $component->runCommands(
                        [
                            ...($isSingleCharacterSelection ? [EditorCommand::make(
                                'extendMarkRange',
                                arguments: ['textColor'],
                            )] : []),
                            EditorCommand::make('unsetTextColor'),
                        ],
                        editorSelection: $arguments['editorSelection'],
                    );

                    return;
                }

                $component->runCommands(
                    [
                        ...($isSingleCharacterSelection ? [EditorCommand::make(
                            'extendMarkRange',
                            arguments: ['link'],
                        )] : []),
                        EditorCommand::make(
                            'setTextColor',
                            arguments: [[
                                'color' => $color,
                            ]],
                        ),
                    ],
                    editorSelection: $arguments['editorSelection'],
                );
            });
    }
}
