@props([
    'debounce' => '500ms',
    'label' => __('webkernel-tables::table.fields.search.label'),
    'labelHidden' => true,
    'onBlur' => false,
    'placeholder' => __('webkernel-tables::table.fields.search.placeholder'),
    'wireModel' => 'tableSearch',
])

@php
    use Webkernel\Platform\Support\Icons\Heroicon;
    use Webkernel\Platform\Support\View\ComponentAttributeBag;
use Webkernel\Platform\Support\View\ComponentAttributeBag as PlatformComponentAttributeBag;
    use Webkernel\Platform\Tables\View\TablesIconAlias;

    $wireModelAttribute = $onBlur ? 'wire:model.live.blur' : "wire:model.live.debounce.{$debounce}";
@endphp

<div
    x-id="['input']"
    {{ $attributes->class(['fi-ta-search-field']) }}
>
    <label
        x-bind:for="$id('input')"
        @class([
            'fi-sr-only' => $labelHidden,
            'fi-ta-cell-label' => ! $labelHidden,
        ])
    >
        {{ $label }}
    </label>

    <x-webkernel-platform::input.wrapper
        inline-prefix
        :prefix-icon="Heroicon::MagnifyingGlass"
        :prefix-icon-alias="TablesIconAlias::SEARCH_FIELD"
        :wire:target="$wireModel"
    >
        <x-webkernel-platform::input
            :attributes="
                (new PlatformComponentAttributeBag)->merge([
                    'autocomplete' => 'off',
                    'inlinePrefix' => true,
                    'maxlength' => 1000,
                    'placeholder' => $placeholder,
                    'type' => 'search',
                    'wire:key' => $this->getId() . '.table.' . $wireModel . '.field.input',
                    $wireModelAttribute => $wireModel,
                    'x-bind:id' => '$id(\'input\')',
                    'x-on:keyup' => 'if ($event.key === \'Enter\') { $wire.$refresh() }',
                ], escape: false)
            "
        />
    </x-webkernel-platform::input.wrapper>
</div>
