@props([
    'canonical' => '',
    'locale' => 'en',
])

@php
    $project = \App\Studio\ProjectCatalog::find('virtual-assistant-asia');
@endphp

<x-studio::project-detail
    :project="$project"
    :canonical="$canonical"
    :locale="$locale"
    :site-ui="$site_ui ?? []"
></x-studio::project-detail>
