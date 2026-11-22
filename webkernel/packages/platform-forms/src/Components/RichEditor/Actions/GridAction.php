<?php

namespace Webkernel\Platform\Forms\Components\RichEditor\Actions;

use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Forms\Components\RichEditor;
use Webkernel\Platform\Forms\Components\RichEditor\EditorCommand;
use Webkernel\Platform\Forms\Components\Select;
use Webkernel\Platform\Forms\Components\TextInput;
use Webkernel\Platform\Forms\Components\Toggle;
use Webkernel\Platform\Schemas\Components\Grid;
use Webkernel\Platform\Support\Enums\Width;

class GridAction
{
    public static function make(): Action
    {
        return Action::make('grid')
            ->label(__('webkernel-forms::components.rich_editor.actions.grid.label'))
            ->modalHeading(__('webkernel-forms::components.rich_editor.actions.grid.modal.heading'))
            ->modalWidth(Width::Large)
            ->schema([
                Grid::make()
                    ->schema([
                        Select::make('preset')
                            ->label(__('webkernel-forms::components.rich_editor.actions.grid.modal.form.preset.label'))
                            ->placeholder(__('webkernel-forms::components.rich_editor.actions.grid.modal.form.preset.placeholder'))
                            ->options([
                                'two' => __('webkernel-forms::components.rich_editor.actions.grid.modal.form.preset.options.two'),
                                'three' => __('webkernel-forms::components.rich_editor.actions.grid.modal.form.preset.options.three'),
                                'four' => __('webkernel-forms::components.rich_editor.actions.grid.modal.form.preset.options.four'),
                                'five' => __('webkernel-forms::components.rich_editor.actions.grid.modal.form.preset.options.five'),
                                'two_start_third' => __('webkernel-forms::components.rich_editor.actions.grid.modal.form.preset.options.two_start_third'),
                                'two_end_third' => __('webkernel-forms::components.rich_editor.actions.grid.modal.form.preset.options.two_end_third'),
                                'two_start_fourth' => __('webkernel-forms::components.rich_editor.actions.grid.modal.form.preset.options.two_start_fourth'),
                                'two_end_fourth' => __('webkernel-forms::components.rich_editor.actions.grid.modal.form.preset.options.two_end_fourth'),
                            ])
                            ->afterStateUpdatedJs(<<<'JS'
                                Object.entries({
                                    two: {
                                        columns: 2,
                                        isAsymmetric: false,
                                        startSpan: null,
                                        endSpan: null,
                                    },
                                    three: {
                                        columns: 3,
                                        isAsymmetric: false,
                                        startSpan: null,
                                        endSpan: null,
                                    },
                                    four: {
                                        columns: 4,
                                        isAsymmetric: false,
                                        startSpan: null,
                                        endSpan: null,
                                    },
                                    five: {
                                        columns: 5,
                                        isAsymmetric: false,
                                        startSpan: null,
                                        endSpan: null,
                                    },
                                    two_start_third: {
                                        columns: 2,
                                        isAsymmetric: true,
                                        startSpan: 1,
                                        endSpan: 2,
                                    },
                                    two_end_third: {
                                        columns: 2,
                                        isAsymmetric: true,
                                        startSpan: 2,
                                        endSpan: 1,
                                    },
                                    two_start_fourth: {
                                        columns: 2,
                                        isAsymmetric: true,
                                        startSpan: 1,
                                        endSpan: 3,
                                    },
                                    two_end_fourth: {
                                        columns: 2,
                                        isAsymmetric: true,
                                        startSpan: 3,
                                        endSpan: 1,
                                    },
                                }[$state] ?? {}).forEach(([key, value]) => $set(key, value))
                            JS)
                            ->dehydrated(false),
                        Select::make('fromBreakpoint')
                            ->label(__('webkernel-forms::components.rich_editor.actions.grid.modal.form.from_breakpoint.label'))
                            ->options([
                                'default' => __('webkernel-forms::components.rich_editor.actions.grid.modal.form.from_breakpoint.options.default'),
                                'sm' => __('webkernel-forms::components.rich_editor.actions.grid.modal.form.from_breakpoint.options.sm'),
                                'md' => __('webkernel-forms::components.rich_editor.actions.grid.modal.form.from_breakpoint.options.md'),
                                'lg' => __('webkernel-forms::components.rich_editor.actions.grid.modal.form.from_breakpoint.options.lg'),
                                'xl' => __('webkernel-forms::components.rich_editor.actions.grid.modal.form.from_breakpoint.options.xl'),
                                '2xl' => __('webkernel-forms::components.rich_editor.actions.grid.modal.form.from_breakpoint.options.2xl'),
                            ])
                            ->default('lg'),
                        Toggle::make('isAsymmetric')
                            ->label(__('webkernel-forms::components.rich_editor.actions.grid.modal.form.is_asymmetric.label'))
                            ->columnSpanFull()
                            ->visibleJs(<<<'JS'
                                Number($get('columns')) === 2
                            JS),
                        TextInput::make('columns')
                            ->label(__('webkernel-forms::components.rich_editor.actions.grid.modal.form.columns.label'))
                            ->integer()
                            ->minValue(2)
                            ->maxValue(12)
                            ->default(2)
                            ->hiddenJs(<<<'JS'
                                $get('isAsymmetric')
                            JS),
                        TextInput::make('startSpan')
                            ->label(__('webkernel-forms::components.rich_editor.actions.grid.modal.form.start_span.label'))
                            ->integer()
                            ->minValue(1)
                            ->maxValue(12)
                            ->visibleJs(<<<'JS'
                                $get('isAsymmetric')
                            JS),
                        TextInput::make('endSpan')
                            ->label(__('webkernel-forms::components.rich_editor.actions.grid.modal.form.end_span.label'))
                            ->integer()
                            ->minValue(1)
                            ->maxValue(12)
                            ->visibleJs(<<<'JS'
                                $get('isAsymmetric')
                            JS),
                    ]),
            ])
            ->action(function (array $arguments, array $data, RichEditor $component): void {
                if ($data['isAsymmetric'] ?? false) {
                    $columns = [(int) ($data['startSpan'] ?? 1), (int) ($data['endSpan'] ?? 1)];
                } else {
                    $columns = array_fill(0, max(2, (int) ($data['columns'] ?? 2)), 1);
                }

                $component->runCommands(
                    [
                        EditorCommand::make(
                            'insertGrid',
                            arguments: [[
                                'fromBreakpoint' => $data['fromBreakpoint'] ?? 'lg',
                                'columns' => $columns,
                            ]],
                        ),
                    ],
                    editorSelection: $arguments['editorSelection'],
                );
            });
    }
}
