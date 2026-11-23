@php
    $project = \App\Studio\ProjectCatalog::find('virtual-assistant-asia');
@endphp

<x-studio::project-detail
    :project="$project"
></x-studio::project-detail>
