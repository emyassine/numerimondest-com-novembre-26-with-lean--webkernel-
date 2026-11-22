@props([
    'id' => 'nm-hero-title',
    'action_href' => null,
    'action_label' => null,
    'secondary_href' => null,
    'secondary_label' => null,
    'positioning_text' => null,
])

<section class="nm-hero" aria-labelledby="{{ $id }}">
    <h1 id="{{ $id }}">{{ $slot }}</h1>
    @if (is_string($action_href) && $action_href !== '' && is_string($action_label) && $action_label !== '')
        <div class="nm-hero-actions">
            <a class="nm-hero-action-primary" href="{{ $action_href }}">{{ $action_label }} <x-webkernel::icon icon="arrow-up-right" set="lucide" width="14" height="14" class="nm-icon-arrow" aria-hidden="true" /></a>
            @if (is_string($secondary_href) && $secondary_href !== '' && is_string($secondary_label) && $secondary_label !== '')
                <a class="nm-hero-action-secondary" href="{{ $secondary_href }}">{{ $secondary_label }}</a>
            @endif
        </div>
        @if (is_string($positioning_text) && $positioning_text !== '')
            <p class="nm-hero-positioning">{{ $positioning_text }}</p>
        @endif
    @endif
</section>
