@props([
    'tenant' => platform()->getTenant(),
])

@php
    use Webkernel\View\Htmlable;

    $src = platform()->getTenantAvatarUrl($tenant);
    $name = platform()->getTenantName($tenant);
    $alt = __('webkernel-platform::layout.avatar.alt', ['name' => ($name instanceof Htmlable) ? html_entity_decode(strip_tags($name->toHtml()), ENT_QUOTES | ENT_HTML5, 'UTF-8') : $name]);
@endphp

<x-webkernel-platform::avatar
    :circular="false"
    :src="$src"
    :alt="$alt"
    :attributes="
        \Webkernel\Platform\Support\prepare_inherited_attributes($attributes)
            ->class(['fi-tenant-avatar'])
    "
/>
