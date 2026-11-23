@props(['canonical' => '', 'locale' => 'en', 'site_ui' => []])
@php $project = \App\Studio\ProjectCatalog::find('the-best-recruit'); @endphp
<x-studio::project-detail :project="$project" :canonical="$canonical" :locale="$locale" :site-ui="$site_ui ?? []" />
