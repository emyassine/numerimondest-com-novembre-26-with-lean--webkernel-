@props([
    'user' => platform()->auth()->user(),
])

@php
    $src = platform()->getUserAvatarUrl($user);
    $alt = __('webkernel-platform::layout.avatar.alt', ['name' => platform()->getUserName($user)]);
@endphp

<x-webkernel-platform::avatar
    :src="$src"
    :alt="$alt"
    :attributes="
        \Webkernel\Platform\Support\prepare_inherited_attributes($attributes)
            ->class(['fi-user-avatar'])
    "
/>
