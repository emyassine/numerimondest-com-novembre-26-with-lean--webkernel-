@php $service = \App\Studio\ServiceCatalog::find('brand-book'); @endphp
<x-studio::service-detail :service="$service" :canonical="$canonical" :locale="$locale" :site-ui="$site_ui ?? []" />
