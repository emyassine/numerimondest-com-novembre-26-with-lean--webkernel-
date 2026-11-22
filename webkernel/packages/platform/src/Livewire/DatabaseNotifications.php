<?php

namespace Webkernel\Platform\Livewire;

use Webkernel\Platform\Enums\DatabaseNotificationsPosition;
use Webkernel\Platform\Facades\Platform;
use Webkernel\Platform\Notifications\Livewire\DatabaseNotifications as BaseComponent;
use \Webkernel\Accounts\Contracts\Authenticatable;
use Webkernel\View\Contracts\View;
use Webkernel\View\ViewResult;
use Webkernel\ORM\Model;
use Webkernel\Liveview\Attributes\Locked;

class DatabaseNotifications extends BaseComponent
{
    #[Locked]
    public ?DatabaseNotificationsPosition $position = null;

    public function getUser(): Model | Authenticatable | null
    {
        return Platform::auth()->user();
    }

    public function getPollingInterval(): ?string
    {
        return Platform::getDatabaseNotificationsPollingInterval();
    }

    public function getTrigger(): View | ViewResult | null
    {
        return (($this->position ?? platform()->getDatabaseNotificationsPosition()) === DatabaseNotificationsPosition::Topbar)
            ? \Webkernel\View\View::resolve('webkernel-platform::components.topbar.database-notifications-trigger')
            : \Webkernel\View\View::resolve('webkernel-platform::components.sidebar.database-notifications-trigger');
    }
}
