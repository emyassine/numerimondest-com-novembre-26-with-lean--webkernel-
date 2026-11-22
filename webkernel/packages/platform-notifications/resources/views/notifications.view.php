@php
    use Webkernel\Platform\Support\Enums\Alignment;
    use Webkernel\Platform\Support\Enums\VerticalAlignment;
@endphp

<div x-data="notificationsComponent()">
    <div
        @class([
            'fi-no',
            'fi-align-' . \Webkernel\Platform\Livewire\Notifications::$alignment->value,
            'fi-vertical-align-' . \Webkernel\Platform\Livewire\Notifications::$verticalAlignment->value,
        ])
        role="status"
        aria-atomic="false"
    >
        @foreach ($notifications as $notification)
            {{ $notification }}
        @endforeach
    </div>

    @if ($broadcastChannel = $this->getBroadcastChannel())
        @script
            <script>
                setUpPlatformBroadcastNotifications({
                    $wire,
                    broadcastChannel: @js($broadcastChannel),
                    event: '.\\Webkernel\\Notifications\\Events\\BroadcastNotificationCreated',
                    onNotification: (notification) =>
                        $wire.handleBroadcastNotification(notification),
                })
            </script>
        @endscript
    @endif
</div>
