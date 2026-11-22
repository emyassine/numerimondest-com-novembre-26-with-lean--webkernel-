@php $service = \App\Studio\ServiceCatalog::find('brand-kit-complete'); @endphp
<x-studio::service-detail :service="$service" :canonical="$canonical" :locale="$locale" :site-ui="$site_ui ?? []" />
