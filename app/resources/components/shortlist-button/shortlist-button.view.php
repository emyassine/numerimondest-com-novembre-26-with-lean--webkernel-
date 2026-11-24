@props([
    'service' => [],
    'return_to' => '',
    'compact' => false,
])

@php
    $savedSlugs = \App\Studio\Shortlist\ServiceShortlist::all();
    $isSaved = in_array($service['slug'] ?? null, $savedSlugs, true);
    $returnTo = $return_to !== '' ? $return_to : (string) ($_SERVER['REQUEST_URI'] ?? '/services');
@endphp

<form class="nm-shortlist-button @if ($compact) nm-shortlist-button--compact @endif" action="/shortlist/toggle" method="post">
    @csrf
    <input type="hidden" name="service" value="{{ $service['slug'] ?? '' }}">
    <input type="hidden" name="action" value="{{ $isSaved ? 'remove' : 'add' }}">
    <input type="hidden" name="return_to" value="{{ $returnTo }}">
    <button type="submit" aria-label="{{ $isSaved ? 'Remove from shortlist' : 'Add to shortlist' }}: {{ $service['name'] ?? 'service' }}" @if ($isSaved) aria-pressed="true" @else aria-pressed="false" @endif>
        @if ($compact){{ $isSaved ? 'Remove' : 'Add' }}@else{{ $isSaved ? 'Remove from shortlist' : 'Add to shortlist' }}@endif
    </button>
</form>
