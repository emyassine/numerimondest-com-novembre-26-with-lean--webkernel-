@php
    $project = \App\Studio\ProjectCatalog::find('feluqa');
@endphp

<x-studio::project-detail
    :project="$project"
></x-studio::project-detail>
