@props([
    'state',
    'offColor' => 'gray',
    'offIcon' => null,
    'onColor' => 'primary',
    'onIcon' => null,
])

@php
    use Webkernel\Platform\Support\Enums\IconSize;
    use Webkernel\Platform\Support\View\Components\ToggleComponent;
    use Webkernel\Utilities\Arr;
@endphp

<button
    x-data="{ state: {{ $state }} }"
    x-bind:aria-checked="state ? 'true' : 'false'"
    x-on:click="state = ! state"
    x-bind:class="
        state ? @js(Arr::toCssClasses([
                    'fi-toggle-on',
                    ...\Webkernel\Platform\Support\get_component_color_classes(ToggleComponent::class, $onColor),
                ])) : @js(Arr::toCssClasses([
                            'fi-toggle-off',
                            ...\Webkernel\Platform\Support\get_component_color_classes(ToggleComponent::class, $offColor),
                        ]))
    "
    @if ($state)
        x-cloak
    @endif
    {{
        $attributes
            ->merge([
                'aria-checked' => $state ? 'true' : 'false',
                'role' => 'switch',
                'type' => 'button',
            ], escape: false)
            ->class(['fi-toggle'])
    }}
>
    <div>
        <div aria-hidden="true">
            {{ \Webkernel\Platform\Support\generate_icon_html($offIcon, size: IconSize::ExtraSmall) }}
        </div>

        <div aria-hidden="true">
            {{ \Webkernel\Platform\Support\generate_icon_html($onIcon, size: IconSize::ExtraSmall) }}
        </div>
    </div>
</button>

@if ($state)
    <div
        x-cloak="inline-flex"
        wire:ignore
        @class([
            'fi-toggle fi-toggle-on fi-hidden',
            ...\Webkernel\Platform\Support\get_component_color_classes(ToggleComponent::class, $onColor),
        ])
    >
        <div>
            <div aria-hidden="true"></div>

            <div aria-hidden="true">
                {{ \Webkernel\Platform\Support\generate_icon_html($onIcon, size: IconSize::ExtraSmall) }}
            </div>
        </div>
    </div>
@endif
