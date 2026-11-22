@props([
    'color' => 'gray',
    'icon' => null,
    'iconSize' => null,
    'tag' => 'div',
])

@php
    use Webkernel\Platform\Support\Enums\IconSize;
    use Webkernel\Platform\Support\View\Components\DropdownComponent\HeaderComponent;

    if (! ($iconSize instanceof IconSize)) {
        $iconSize = filled($iconSize) ? (IconSize::tryFrom($iconSize) ?? $iconSize) : null;
    }
@endphp

<{{ $tag }}
    {{
        $attributes
            ->class([
                'fi-dropdown-header',
            ])
            ->color(HeaderComponent::class, $color)
    }}
>
    {{ \Webkernel\Platform\Support\generate_icon_html($icon, size: $iconSize) }}

    <span>
        {{ $slot }}
    </span>
</{{ $tag }}>
