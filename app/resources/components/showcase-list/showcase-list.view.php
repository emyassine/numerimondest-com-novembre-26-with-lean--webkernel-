@props([
    'cards' => [],
    'card_type' => 'showcase',
    'variant' => 'stacked',
    'modifier' => '',
    'id' => null,
    'label' => null,
    'heading_level' => 2,
])

@php
    $variant = in_array($variant, ['stacked', 'grid'], true) ? $variant : 'stacked';
    $card_type = in_array($card_type, ['showcase', 'project'], true) ? $card_type : 'showcase';
    $listClasses = array_filter([
        'nm-projects',
        $modifier,
        $variant === 'grid' ? 'nm-projects--grid' : null,
    ]);
@endphp

<section
    class="{{ implode(' ', $listClasses) }}"
    @if (is_string($id) && $id !== '') id="{{ $id }}" @endif
    @if (is_string($label) && $label !== '') aria-label="{{ $label }}" @endif
>
    @foreach ($cards as $card)
        @if ($card_type === 'project')
            <x-studio::project-card :project="$card" :heading-level="$heading_level" />
        @else
            <x-studio::showcase-card :card="$card" :heading-level="$heading_level" />
        @endif
    @endforeach
</section>
