@props([
    'services' => [],
    'id' => null,
    'label' => 'Numerimondes service marketplace',
])

<section
    class="nm-marketplace-grid"
    @if (is_string($id) && $id !== '') id="{{ $id }}" @endif
    aria-label="{{ $label }}"
>
    @foreach ($services as $service)
        <x-studio::marketplace-card :service="$service" />
    @endforeach
</section>
