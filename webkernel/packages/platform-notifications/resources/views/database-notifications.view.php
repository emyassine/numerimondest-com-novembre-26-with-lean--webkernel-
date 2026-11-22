@php
    use Webkernel\Platform\Notifications\View\NotificationsIconAlias;
    use Webkernel\Platform\Support\Enums\Alignment;
    use Webkernel\Platform\Support\Icons\Heroicon;
    use Webkernel\Platform\Support\View\ComponentAttributeBag;
use Webkernel\Platform\Support\View\ComponentAttributeBag as PlatformComponentAttributeBag;
    use Webkernel\Platform\Support\View\Components\BadgeComponent;
    use \Webkernel\Database\Contracts\Pagination\Paginator;

    $notifications = $this->getNotifications();
    $unreadNotificationsCount = $this->getUnreadNotificationsCount();
    $hasNotifications = $notifications->count();
    $isPaginated = $notifications instanceof Paginator && $notifications->hasPages();
    $pollingInterval = $this->getPollingInterval();
@endphp

<div class="fi-no-database">
    {{-- The focus trap autofocuses the modal window itself when the slide-over opens, since the first tabbable element is the `Mark all as read` header action, which `Enter` would otherwise immediately (and irreversibly) trigger. The window must carry the `autofocus` attribute because the focus trap resolves it once, when the modal first initializes, and the window is always rendered. --}}
    <x-webkernel-platform::modal
        :alignment="$hasNotifications ? null : Alignment::Center"
        aria-labelledby="database-notifications.heading"
        close-button
        :description="$hasNotifications ? null : __('webkernel-notifications::database.modal.empty.description')"
        :extra-modal-window-attribute-bag="
            new PlatformComponentAttributeBag([
                'autofocus' => true,
                'tabindex' => '-1',
            ])
        "
        :heading="$hasNotifications ? null : __('webkernel-notifications::database.modal.empty.heading')"
        :icon="$hasNotifications ? null : Heroicon::OutlinedBellSlash"
        :icon-alias="
            $hasNotifications
            ? null
            : NotificationsIconAlias::DATABASE_MODAL_EMPTY_STATE
        "
        :icon-color="$hasNotifications ? null : 'gray'"
        id="database-notifications"
        slide-over
        :sticky-header="$hasNotifications"
        teleport="body"
        width="md"
        class="fi-no-database"
        :attributes="
            new PlatformComponentAttributeBag([
                'wire:poll.' . $pollingInterval => $pollingInterval ? '' : false,
            ])
        "
    >
        @if ($trigger = $this->getTrigger())
            <x-slot name="trigger">
                {{ $trigger->with(['unreadNotificationsCount' => $unreadNotificationsCount]) }}
            </x-slot>
        @endif

        @if ($hasNotifications)
            <x-slot name="header">
                <div>
                    <h2
                        id="database-notifications.heading"
                        class="fi-modal-heading"
                    >
                        {{ __('webkernel-notifications::database.modal.heading') }}

                        @if ($unreadNotificationsCount)
                            <span
                                {{
                                    (new PlatformComponentAttributeBag)->color(BadgeComponent::class, 'primary')->class([
                                        'fi-badge fi-size-xs',
                                    ])
                                }}
                            >
                                {{ $unreadNotificationsCount }}
                            </span>
                        @endif
                    </h2>

                    <div class="fi-ac">
                        @if ($unreadNotificationsCount && $this->markAllNotificationsAsReadAction?->isVisible())
                            {{ $this->markAllNotificationsAsReadAction }}
                        @endif

                        @if ($this->clearNotificationsAction?->isVisible())
                            {{ $this->clearNotificationsAction }}
                        @endif
                    </div>
                </div>
            </x-slot>

            <div
                aria-label="{{ __('webkernel-notifications::database.modal.heading') }}"
                role="list"
                class="fi-no-notifications"
            >
                @foreach ($notifications as $notification)
                    <div
                        role="listitem"
                        wire:key="{{ $notification->getKey() }}.database-notifications.ctn"
                        @class([
                            'fi-no-notification-read-ctn' => ! $notification->unread(),
                            'fi-no-notification-unread-ctn' => $notification->unread(),
                        ])
                    >
                        @if ($notification->unread())
                            <span class="fi-sr-only">
                                {{ __('webkernel-notifications::database.modal.unread_label') }}
                            </span>
                        @endif

                        {{ $this->getNotification($notification)->inline() }}
                    </div>
                @endforeach
            </div>

            @if ($broadcastChannel = $this->getBroadcastChannel())
                @script
                    <script>
                        setUpPlatformBroadcastNotifications({
                            $wire,
                            broadcastChannel: @js($broadcastChannel),
                            event: '.database-notifications.sent',
                            onNotification: () => $wire.call('$refresh'),
                        })
                    </script>
                @endscript
            @endif

            @if ($isPaginated)
                <x-slot name="footer">
                    <x-webkernel-platform::pagination :paginator="$notifications" />
                </x-slot>
            @endif
        @endif
    </x-webkernel-platform::modal>
</div>
