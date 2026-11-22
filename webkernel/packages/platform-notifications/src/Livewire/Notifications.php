<?php

namespace Webkernel\Platform\Notifications\Livewire;

use Webkernel\Platform\Notifications\Collection;
use Webkernel\Platform\Notifications\Notification;
use Webkernel\Platform\Notifications\NotificationMailbox;
use Webkernel\Platform\Support\Enums\Alignment;
use Webkernel\Platform\Support\Enums\VerticalAlignment;
use \Webkernel\Accounts\Contracts\Authenticatable;
use Webkernel\View\Contracts\View;
use Webkernel\View\ViewResult;
use Webkernel\ORM\Model;
use Webkernel\Validation\Validation;
use Webkernel\Liveview\Attributes\On;
use Webkernel\Liveview\Component;

class Notifications extends Component
{
    // Used to check if Livewire messages should trigger notification animations.
    public bool $isPlatformNotificationsComponent = true;

    public Collection $notifications;

    public static Alignment $alignment = Alignment::Right;

    public static VerticalAlignment $verticalAlignment = VerticalAlignment::Start;

    public static ?string $authGuard = null;

    public function mount(): void
    {
        $this->notifications = new Collection;
    }

    /** @param array<string> $batches */
    #[On('notificationsSent')]
    public function pullNotificationsFromSession(array $batches = []): void
    {
        Validation::make(['batches' => $batches], [
            'batches' => ['array', 'max:50'],
            'batches.*' => ['required', 'string', 'regex:/\A[a-f0-9]{40}\z/'],
        ])->validate();

        foreach (app(NotificationMailbox::class)->pull($batches) as $notification) {
            $notification = Notification::fromArray($notification);

            $this->pushNotification($notification);
        }
    }

    /**
     * @param  array<string, mixed>  $notification
     */
    #[On('notificationSent')]
    public function pushNotificationFromEvent(array $notification): void
    {
        $notification = Notification::fromArray($notification);

        $this->pushNotification($notification);
    }

    #[On('notificationClosed')]
    public function removeNotification(string $id): void
    {
        if (! $this->notifications->has($id)) {
            return;
        }

        $this->notifications->forget($id);
    }

    /**
     * @param  array<string, mixed>  $notification
     */
    public function handleBroadcastNotification(array $notification): void
    {
        if (($notification['format'] ?? null) !== 'platform') {
            return;
        }

        $this->pushNotification(Notification::fromArray($notification));
    }

    protected function pushNotification(Notification $notification): void
    {
        $this->notifications->put(
            $notification->getId(),
            $notification,
        );
    }

    public function getUser(): Model | Authenticatable | null
    {
        return auth(static::$authGuard)->user();
    }

    public function getBroadcastChannel(): ?string
    {
        $user = $this->getUser();

        if (! $user) {
            return null;
        }

        if (method_exists($user, 'receivesBroadcastNotificationsOn')) {
            return $user->receivesBroadcastNotificationsOn();
        }

        $userClass = str_replace('\\', '.', $user::class);

        return "{$userClass}.{$user->getKey()}";
    }

    public static function alignment(Alignment $alignment): void
    {
        static::$alignment = $alignment;
    }

    public static function verticalAlignment(VerticalAlignment $alignment): void
    {
        static::$verticalAlignment = $alignment;
    }

    public static function authGuard(?string $guard): void
    {
        static::$authGuard = $guard;
    }

    public function render(): View|ViewResult
    {
        return \Webkernel\View\View::resolve('webkernel-notifications::notifications');
    }
}
