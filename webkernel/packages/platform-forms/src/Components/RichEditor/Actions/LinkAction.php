<?php

namespace Webkernel\Platform\Forms\Components\RichEditor\Actions;

use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Forms\Components\Checkbox;
use Webkernel\Platform\Forms\Components\RichEditor;
use Webkernel\Platform\Forms\Components\RichEditor\EditorCommand;
use Webkernel\Platform\Forms\Components\TextInput;
use Webkernel\Platform\Support\Enums\Width;

class LinkAction
{
    public static function make(): Action
    {
        return Action::make('link')
            ->label(__('webkernel-forms::components.rich_editor.actions.link.label'))
            ->modalHeading(__('webkernel-forms::components.rich_editor.actions.link.modal.heading'))
            ->modalWidth(Width::Large)
            ->fillForm(fn (array $arguments): array => [
                'url' => $arguments['url'] ?? null,
                'shouldOpenInNewTab' => $arguments['shouldOpenInNewTab'] ?? false,
            ])
            ->schema([
                TextInput::make('url')
                    ->label(__('webkernel-forms::components.rich_editor.actions.link.modal.form.url.label'))
                    ->inputMode('url'),
                Checkbox::make('shouldOpenInNewTab')
                    ->label(__('webkernel-forms::components.rich_editor.actions.link.modal.form.should_open_in_new_tab.label')),
            ])
            ->action(function (array $arguments, array $data, RichEditor $component): void {
                $isSingleCharacterSelection = ($arguments['editorSelection']['head'] ?? null) === ($arguments['editorSelection']['anchor'] ?? null);

                if (blank($data['url'] ?? null)) {
                    $component->runCommands(
                        [
                            ...($isSingleCharacterSelection ? [EditorCommand::make(
                                'extendMarkRange',
                                arguments: ['link'],
                            )] : []),
                            EditorCommand::make('unsetLink'),
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
                            'setLink',
                            arguments: [[
                                'href' => $data['url'],
                                'target' => $data['shouldOpenInNewTab'] ? '_blank' : null,
                            ]],
                        ),
                    ],
                    editorSelection: $arguments['editorSelection'],
                );
            });
    }
}
