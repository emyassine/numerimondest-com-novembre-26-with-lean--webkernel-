@php $service = \App\Studio\ServiceCatalog::find('crm-client-portal'); @endphp
<x-studio::service-detail :service="$service" :canonical="$canonical" :locale="$locale" :site-ui="$site_ui ?? []" />
