@php
/** @var \Webkernel\View\AttributeBag $attributes */
@endphp
@props(['icon' => '', 'set' => 'lucide', 'width' => 16, 'height' => 16])
@php
$svg = icon(
    (string) $icon,
    (string) $attributes->get('class', ''),
    (string) $attributes->get('style', ''),
    (string) $set,
    (int) $width,
    (int) $height,
    $attributes->except(['class', 'style'])->to_array(),
);
@endphp
{!! $svg !!}
