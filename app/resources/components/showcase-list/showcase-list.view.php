@props([
    'cards' => [],
    'card_type' => 'showcase',
    'variant' => 'stacked',
    'modifier' => '',
    'id' => null,
    'label' => null,
    'heading_level' => 2,
])

@php
    $variant = in_array($variant, ['stacked', 'grid'], true) ? $variant : 'stacked';
    $card_type = in_array($card_type, ['showcase', 'project'], true) ? $card_type : 'showcase';
    $listClasses = array_filter([
        'nm-projects',
        $modifier,
        $variant === 'grid' ? 'nm-projects--grid' : null,
    ]);
@endphp
<style>
.nm-projects--grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(min(100%, 420px), 1fr));
  gap: 12px;
}

.nm-projects--grid .nm-project {
  height: 70vh;
  min-height: 480px;
}

.nm-projects--grid .nm-project-link { padding: 35px; }

.nm-projects--grid.nm-projects--listing {
  grid-template-columns: repeat(4, minmax(0, 1fr));
}

.nm-projects--grid.nm-projects--listing .nm-project {
  height: 55vh;
  min-height: 380px;
}

@media (max-width: 1100px) and (min-width: 701px) {
  .nm-projects--grid.nm-projects--listing {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .nm-projects--grid.nm-projects--listing .nm-project {
    height: 52vh;
    min-height: 400px;
  }

  .nm-projects--grid.nm-projects--listing .nm-project-link {
    padding: 24px;
  }
}

@media (max-width: 700px) {
  .nm-projects--grid,
  .nm-projects--grid.nm-projects--listing {
    padding: 0;
    grid-template-columns: minmax(0, 1fr);
    gap: 8px;
  }

  .nm-projects--grid .nm-project { height: 78vh; min-height: 480px; }
  .nm-projects--grid.nm-projects--listing .nm-project {
    height: 72svh;
    min-height: 360px;
  }

  .nm-projects--grid .nm-project-link { padding: 25px 20px; }
}
</style>
<section
    class="{{ implode(' ', $listClasses) }}"
    @if (is_string($id) && $id !== '') id="{{ $id }}" @endif
    @if (is_string($label) && $label !== '') aria-label="{{ $label }}" @endif
>
    @foreach ($cards as $card)
        @if ($card_type === 'project')
            <x-studio::project-card :project="$card" :heading-level="$heading_level" />
        @else
            <x-studio::showcase-card :card="$card" :heading-level="$heading_level" />
        @endif
    @endforeach
</section>
