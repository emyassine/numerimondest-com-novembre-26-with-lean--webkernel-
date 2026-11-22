<?php

namespace Webkernel\Platform\Notifications\Livewire;

use Webkernel\DateTime\CarbonInterface;
use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Actions\Concerns\InteractsWithActions;
use Webkernel\Platform\Actions\Contracts\HasActions;
use Webkernel\Platform\Notifications\Notification;
use Webkernel\Platform\Schemas\Concerns\InteractsWithSchemas;
use Webkernel\Platform\Schemas\Contracts\HasSchemas;
use \Webkernel\Accounts\Contracts\Authenticatable;
use \Webkernel\Database\Contracts\Pagination\Paginator;
use Webkernel\View\Contracts\View;
use Webkernel\View\ViewResult;
use Webkernel\ORM\Builder;
use Webkernel\ORM\Model;
use Webkernel\ORM\Relations\Relation;
use \Webkernel\Notifications\DatabaseNotification;
use \Webkernel\Notifications\DatabaseNotificationCollection;
use Webkernel\Utilities\Str;
use Webkernel\Liveview\Attributes\On;
use Webkernel\Liveview\Component;
use Webkernel\Liveview\WithPagination;

class DatabaseNotifications extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use WithPagination;

    public static bool $isPaginated = true;

    public static ?string $trigger = null;

    public static ?string $pollingInterval = '30s';

    public static ?string $authGuard = null;

    #[On('databaseNotificationsSent')]
    public function refresh(): void {}

    #[On('notificationClosed')]
    public function removeNotification(string $id): void
    {
        if (! Str::isUuid($id)) {
            return;
        }

        $this->getNotificationsQuery()
            ->where('id', $id)
            ->delete();
    }

    #[On('markedNotificationAsRead')]
    public function markNotificationAsRead(string $id): void
    {
        if (! Str::isUuid($id)) {
            return;
        }

        $this->getNotificationsQuery()
            ->where('id', $id)
            ->update(['read_at' => now()]);
    }

    #[On('markedNotificationAsUnread')]
    public function markNotificationAsUnread(string $id): void
    {
        if (! Str::isUuid($id)) {
            return;
        }

        $this->getNotificationsQuery()
            ->where('id', $id)
            ->update(['read_at' => null]);
    }

    public function clearNotifications(): void
    {
        $this->getNotificationsQuery()->delete();
    }

    public function markAllNotificationsAsRead(): void
    {
        $this->getUnreadNotificationsQuery()->update(['read_at' => now()]);
    }

    public function getNotifications(): DatabaseNotificationCollection | Paginator
    {
        if (! $this->isPaginated()) {
            /** @phpstan-ignore-next-line */
            return $this->getNotificationsQuery()->get();
        }

        return $this->getNotificationsQuery()->simplePaginate(50, pageName: 'database-notifications-page');
    }

    public function isPaginated(): bool
    {
        return static::$isPaginated;
    }

    public function getNotificationsQuery(): Builder | Relation
    {
        $user = $this->getUser();

        if (! $user) {
            abort(401);
        }

        /** @phpstan-ignore-next-line */
        return $user->notifications()->where('data->format', 'platform');
    }

    public function getUnreadNotificationsQuery(): Builder | Relation
    {
        /** @phpstan-ignore-next-line */
        return $this->getNotificationsQuery()->unread();
    }

    public function getUnreadNotificationsCount(): int
    {
        return $this->getUnreadNotificationsQuery()->count();
    }

    public function getPollingInterval(): ?string
    {
        return static::$pollingInterval;
    }

    public function getTrigger(): View | ViewResult | null
    {
        $viewPath = static::$trigger;

        if (blank($viewPath)) {
            return null;
        }

        return \Webkernel\View\View::resolve($viewPath);
    }

    public function markAllNotificationsAsReadAction(): Action
    {
        return Action::make('markAllNotificationsAsRead')
            ->link()
            ->label(__('webkernel-notifications::database.modal.actions.mark_all_as_read.label'))
            ->action('markAllNotificationsAsRead');
    }

    public function clearNotificationsAction(): Action
    {
        return Action::make('clearNotifications')
            ->link()
            ->color('danger')
            ->label(__('webkernel-notifications::database.modal.actions.clear.label'))
            ->action('clearNotifications')
            ->close();
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

    public function getNotification(DatabaseNotification $notification): Notification
    {
        return Notification::fromDatabase($notification)
            ->date($this->formatNotificationDate($notification->getAttributeValue('created_at')));
    }

    protected function formatNotificationDate(CarbonInterface $date): string
    {
        return $date->diffForHumans();
    }

    public static function trigger(?string $trigger): void
    {
        static::$trigger = $trigger;
    }

    public static function pollingInterval(?string $interval): void
    {
        static::$pollingInterval = $interval;
    }

    public static function authGuard(?string $guard): void
    {
        static::$authGuard = $guard;
    }

    /**
     * @return array<string>
     */
    public function queryStringHandlesPagination(): array
    {
        return [];
    }

    public function render(): View|ViewResult
    {
        return \Webkernel\View\View::resolve('webkernel-notifications::database-notifications');
    }

    public function placeholder(): string
    {
        return '<div>' . $this->getTrigger()?->with(['unreadNotificationsCount' => $this->getUnreadNotificationsCount()])->render() . '</div>';
    }
}
