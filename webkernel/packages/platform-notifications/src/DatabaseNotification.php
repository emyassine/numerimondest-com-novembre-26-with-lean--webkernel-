<?php

namespace Webkernel\Platform\Notifications;

use \Webkernel\Queue\Bus\Queueable;
use \Webkernel\Queue\Contracts\ShouldQueue;
use Webkernel\Utilities\Contracts\Arrayable;
use Webkernel\ORM\Model;
use \Webkernel\Notifications\Notification as BaseNotification;

class DatabaseNotification extends BaseNotification implements Arrayable, ShouldQueue
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
        return ['database'];
    }

    /**
     * @param  Model  $notifiable
     * @return array<string, mixed>
     */
    public function toDatabase($notifiable): array
    {
        return $this->data;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
