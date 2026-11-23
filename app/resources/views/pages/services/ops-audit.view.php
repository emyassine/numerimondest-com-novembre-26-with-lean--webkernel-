@props(['canonical' => '', 'locale' => 'en', 'site_ui' => []])
@php $service = \App\Studio\ServiceCatalog::find('ops-audit'); @endphp
<x-studio::service-detail :service="$service" :canonical="$canonical" :locale="$locale" :site-ui="$site_ui ?? []" />
