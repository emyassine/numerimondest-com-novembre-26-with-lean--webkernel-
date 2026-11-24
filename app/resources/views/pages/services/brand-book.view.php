@php $service = \App\Studio\Catalog\ServiceCatalog::find('brand-book'); @endphp
<x-studio::service-detail :service="$service" />
