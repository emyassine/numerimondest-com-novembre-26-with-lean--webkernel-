<?php

namespace Webkernel\Platform\Notifications\Events;

use \Webkernel\Broadcasting\InteractsWithSockets;
use \Webkernel\Broadcasting\PrivateChannel;
use \Webkernel\Accounts\Contracts\Authenticatable;
use \Webkernel\Broadcasting\Contracts\ShouldBroadcast;
use Webkernel\ORM\Model;
use \Webkernel\Events\Dispatchable;
use \Webkernel\Queue\SerializesModels;

class DatabaseNotificationsSent implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        protected Model | Authenticatable $user,
    ) {}

    public function broadcastOn(): string
    {
        if (method_exists($this->user, 'receivesBroadcastNotificationsOn')) {
            return new PrivateChannel($this->user->receivesBroadcastNotificationsOn());
        }

        $userClass = str_replace('\\', '.', $this->user::class);

        return new PrivateChannel("{$userClass}.{$this->user->getKey()}");
    }

    public function broadcastAs(): string
    {
        return 'database-notifications.sent';
    }
}
