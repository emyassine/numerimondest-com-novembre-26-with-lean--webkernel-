@props([
    'canonical' => '',
    'locale' => 'en',
    'site_ui' => [],
])

@php
    $project = \App\Studio\ProjectCatalog::find('caldezza');
@endphp

<x-studio::project-detail
    :project="$project"
    :canonical="$canonical"
    :locale="$locale"
    :site-ui="$site_ui ?? []"
></x-studio::project-detail>
