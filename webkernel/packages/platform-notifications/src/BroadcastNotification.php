<?php

namespace Webkernel\Platform\Notifications;

use \Webkernel\Queue\Bus\Queueable;
use \Webkernel\Queue\Contracts\ShouldQueue;
use Webkernel\ORM\Model;
use \Webkernel\Notifications\Messages\BroadcastMessage;
use \Webkernel\Notifications\Notification as BaseNotification;

class BroadcastNotification extends BaseNotification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public array $data,
    ) {}

    /**
     * @param  Model  $notifiable
     * @return array<string>
     */
    public function via($notifiable): array
    {
        return ['broadcast'];
    }

    /**
     * @param  Model  $notifiable
     */
    public function toBroadcast($notifiable): BroadcastMessage
    {
        return (new BroadcastMessage($this->data))
            ->onConnection($this->connection)
            ->onQueue($this->queue);
    }
}
