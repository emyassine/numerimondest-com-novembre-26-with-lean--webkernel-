@props([
    'title' => null,
    'body' => null,
    'status' => 'info',
    'duration' => 5000,
])
<div
    x-data="{ visible: true, timer: null, init() { if ({{ (int) $duration }} > 0) this.timer = setTimeout(() => this.visible = false, {{ (int) $duration }}) } }"
    x-show="visible"
    x-transition
    role="{{ $status === 'danger' ? 'alert' : 'status' }}"
    class="fi-no-notification fi-status-{{ $status }}"
    {{ $attributes }}
>
    <div class="fi-no-notification-main">
        @if (filled($title))
            <h3 class="fi-no-notification-title">{{ $title }}</h3>
        @endif
        @if (filled($body))
            <div class="fi-no-notification-body">{{ $body }}</div>
        @endif
    </div>
    <button type="button" x-on:click="visible = false" aria-label="Dismiss" class="fi-icon-btn fi-no-notification-close-btn">×</button>
</div>
