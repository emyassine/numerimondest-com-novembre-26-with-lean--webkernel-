<?php

namespace Webkernel\Platform\Auth\Notifications;

use \Webkernel\Accounts\Notifications\ResetPassword as BaseNotification;
use \Webkernel\Queue\Bus\Queueable;
use \Webkernel\Queue\Contracts\ShouldQueue;

class ResetPassword extends BaseNotification implements ShouldQueue
{
    use Queueable;

    public string $url;

    protected function resetUrl($notifiable): string
    {
        return $this->url;
    }
}
