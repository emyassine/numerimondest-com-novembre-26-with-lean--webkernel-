<?php

namespace Webkernel\Platform\Auth\Notifications;

use \Webkernel\Accounts\Notifications\VerifyEmail as BaseNotification;
use \Webkernel\Queue\Bus\Queueable;
use \Webkernel\Queue\Contracts\ShouldQueue;

class VerifyEmail extends BaseNotification implements ShouldQueue
{
    use Queueable;

    public string $url;

    protected function verificationUrl($notifiable): string
    {
        return $this->url;
    }
}
