@props(['canonical' => '', 'locale' => 'en', 'site_ui' => []])
@php $service = \App\Studio\ServiceCatalog::find('the-best-recruit'); @endphp
<x-studio::service-detail :service="$service" :canonical="$canonical" :locale="$locale" :site-ui="$site_ui ?? []" />
