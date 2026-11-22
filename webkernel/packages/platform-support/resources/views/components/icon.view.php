@props([
    'alias' => null,
    'icon' => null,
    'size' => null,
])

{{ \Webkernel\Platform\Support\generate_icon_html($icon, $alias, $attributes, $size) }}
