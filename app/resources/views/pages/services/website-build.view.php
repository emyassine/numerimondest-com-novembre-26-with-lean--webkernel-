@php $service = \App\Studio\ServiceCatalog::find('website-build'); @endphp
<x-studio::service-detail :service="$service" :canonical="$canonical" :locale="$locale" :site-ui="$site_ui ?? []" />
