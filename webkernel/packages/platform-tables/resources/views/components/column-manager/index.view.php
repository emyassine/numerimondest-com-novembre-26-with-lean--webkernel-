@props([
    'applyAction',
    'columns' => null,
    'hasReorderableColumns',
    'hasToggleableColumns',
    'headingTag' => 'h3',
    'reorderAnimationDuration' => 300,
    'resetActionPosition' => null,
])

@php
    use Webkernel\Platform\Support\View\ComponentAttributeBag;
use Webkernel\Platform\Support\View\ComponentAttributeBag as PlatformComponentAttributeBag;
    use Webkernel\Platform\Tables\Enums\ColumnManagerResetActionPosition;

    $resetActionPosition ??= ColumnManagerResetActionPosition::Header;
@endphp

<div
    x-data="platformTableColumnManager({
                columns: $wire.entangle('tableColumns'),
                isLive: {{ $applyAction->isVisible() ? 'false' : 'true' }},
            })"
    class="fi-ta-col-manager"
>
    <div class="fi-ta-col-manager-header">
        <{{ $headingTag }} class="fi-ta-col-manager-heading">
            {{ __('webkernel-tables::table.column_manager.heading') }}
        </{{ $headingTag }}>

        @if ($resetActionPosition === ColumnManagerResetActionPosition::Header)
            <div>
                <x-webkernel-platform::link
                    :attributes="
                        \Webkernel\Platform\Support\prepare_inherited_attributes(
                            new PlatformComponentAttributeBag([
                                'color' => 'danger',
                                'tag' => 'button',
                                'wire:click' => 'resetTableColumnManager',
                                'wire:loading.remove.delay.' . config('platform.liveview_loading_delay', 'default') => '',
                                'wire:target' => 'resetTableColumnManager',
                                'x-on:click' => 'resetDeferredColumns',
                            ])
                        )
                    "
                >
                    {{ __('webkernel-tables::table.column_manager.actions.reset.label') }}
                </x-webkernel-platform::link>
            </div>
        @endif
    </div>

    <x-webkernel-tables::column-manager.content
        :columns="$columns"
        :has-reorderable-columns="$hasReorderableColumns"
        :has-toggleable-columns="$hasToggleableColumns"
        :reorder-animation-duration="$reorderAnimationDuration"
    />

    @if ($applyAction->isVisible() || $resetActionPosition === ColumnManagerResetActionPosition::Footer)
        <div class="fi-ta-col-manager-actions-ctn">
            @if ($applyAction->isVisible())
                {{ $applyAction }}
            @endif

            @if ($resetActionPosition === ColumnManagerResetActionPosition::Footer)
                <x-webkernel-platform::button
                    color="danger"
                    wire:click="resetTableColumnManager"
                    x-on:click="resetDeferredColumns"
                >
                    {{ __('webkernel-tables::table.column_manager.actions.reset.label') }}
                </x-webkernel-platform::button>
            @endif
        </div>
    @endif
</div>
