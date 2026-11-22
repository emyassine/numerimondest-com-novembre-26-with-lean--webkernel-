@php $service = \App\Studio\ServiceCatalog::find('revenue-desk-scale'); @endphp
<x-studio::service-detail :service="$service" :canonical="$canonical" :locale="$locale" :site-ui="$site_ui ?? []" />
