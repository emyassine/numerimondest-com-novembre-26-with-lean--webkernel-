@php $service = \App\Studio\ServiceCatalog::find('automation-sprint'); @endphp
<x-studio::service-detail :service="$service" :canonical="$canonical" :locale="$locale" :site-ui="$site_ui ?? []" />
