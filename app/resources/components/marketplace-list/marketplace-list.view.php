@props([
    'services' => [],
    'id' => null,
    'label' => 'Numerimondes service marketplace',
])
<style>
.nm-marketplace-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(min(100%, 260px), 1fr));
  align-items: stretch;
  gap: 12px;
}

@media (max-width: 700px) {
  .nm-marketplace-grid { gap: 8px; }
}
</style>
<section
    class="nm-marketplace-grid"
    @if (is_string($id) && $id !== '') id="{{ $id }}" @endif
    aria-label="{{ $label }}"
>

  <style>
  .fi-section:not(.fi-section-not-contained):not(.fi-divided)>.fi-section-content-ctn>.fi-section-content, .fi-section:not(.fi-section-not-contained).fi-divided>.fi-section-content-ctn>.fi-section-content>* {
       padding: 0;
  }
  </style>
    @foreach ($services as $service)
        <x-studio::marketplace-card :service="$service" :card-class="'nm-marketplace-card-item-'.$loop->iteration" />
    @endforeach
</section>
