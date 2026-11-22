<?php

namespace Webkernel\Platform\Auth\MultiFactor\Email\Notifications;

use \Webkernel\Queue\Bus\Queueable;
use \Webkernel\Queue\Contracts\ShouldQueue;
use \Webkernel\Notifications\Messages\MailMessage;
use \Webkernel\Notifications\Notification;
use SensitiveParameter;

class VerifyEmailAuthentication extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        #[SensitiveParameter]
        public string $code,
        public int $codeExpiryMinutes,
    ) {}

    /**
     * @return array<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('webkernel-platform::auth/multi-factor/email/notifications/verify-email-authentication.subject'))
            ->line(trans_choice('webkernel-platform::auth/multi-factor/email/notifications/verify-email-authentication.lines.0', $this->codeExpiryMinutes, ['code' => $this->code, 'minutes' => $this->codeExpiryMinutes]))
            ->line(trans_choice('webkernel-platform::auth/multi-factor/email/notifications/verify-email-authentication.lines.1', $this->codeExpiryMinutes, ['code' => $this->code, 'minutes' => $this->codeExpiryMinutes]));
    }
}
