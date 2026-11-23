@php
    $project = \App\Studio\ProjectCatalog::find('caldezza');
@endphp

<x-studio::project-detail
    :project="$project"
></x-studio::project-detail>
