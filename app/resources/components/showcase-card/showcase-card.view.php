@props([
    'card' => [],
    'heading_level' => 2,
])

@php
    $heading_level = in_array((int) $heading_level, [2, 3], true) ? (int) $heading_level : 2;
@endphp

<article class="nm-project {{ $card['class'] ?? '' }}">
    <a class="nm-project-link" href="{{ $card['href'] }}">
        <img
            class="nm-project-image"
            src="{{ $card['image'] }}"
            alt="{{ $card['alt'] ?? $card['name'] }}"
        >

        <div class="nm-project-info">
            <div>
                @if ($heading_level === 3)
                    <h3 class="nm-project-name">{{ $card['name'] }}</h3>
                @else
                    <h2 class="nm-project-name">{{ $card['name'] }}</h2>
                @endif

                <p class="nm-project-description">{{ $card['description'] }}</p>
            </div>

            <div class="nm-project-tags" @if (! empty($card['tags_label'])) aria-label="{{ $card['tags_label'] }}" @endif>
                @foreach ($card['tags'] ?? [] as $tag)
                    <span>{{ $tag }}</span>
                @endforeach
            </div>
        </div>
    </a>
</article>
