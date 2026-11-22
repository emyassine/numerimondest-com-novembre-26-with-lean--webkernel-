@props([
    'project' => [],
    'heading_level' => 2,
])

@php
    $heading_level = in_array((int) $heading_level, [2, 3], true) ? (int) $heading_level : 2;
    $card = [
        'name' => $project['name'],
        'description' => $project['card_description'],
        'image' => simple_site('studio', $project['card_image']),
        'alt' => $project['card_image_alt'],
        'href' => simple_site(
            'studio',
            (($project['kind'] ?? 'initiative') === 'service' ? 'services/' : 'projects/').$project['slug'],
        ),
        'class' => 'project--'.$project['slug'],
        'tags' => [$project['status'], $project['category']],
        'tags_label' => 'Project status and category',
    ];
@endphp

<x-studio::showcase-card :card="$card" :heading-level="$heading_level" />
