<?php

namespace Webkernel\Platform\Auth\Pages\EmailVerification;

use Webkernel\Liveview\Features\SupportRateLimiting\Exceptions\TooManyRequestsException;
use Webkernel\Liveview\Features\SupportRateLimiting\WithRateLimiting;
use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Auth\Notifications\VerifyEmail;
use Webkernel\Platform\Facades\Platform;
use Webkernel\Platform\Notifications\Notification;
use Webkernel\Platform\Pages\SimplePage;
use Webkernel\Platform\Schemas\Components\Text;
use Webkernel\Platform\Schemas\Concerns\RestrictsFileUploadsToSchemaComponents;
use Webkernel\Platform\Schemas\Schema;
use \Webkernel\Accounts\Contracts\MustVerifyEmail;
use Webkernel\View\Htmlable;
use Webkernel\Liveview\Features\SupportRateLimiting\RateLimiter;
use Webkernel\View\HtmlString;
use LogicException;

/**
 * @property-read Action $resendNotificationAction
 */
class EmailVerificationPrompt extends SimplePage
{
    use RestrictsFileUploadsToSchemaComponents;
    use WithRateLimiting;

    public function mount(): void
    {
        if ((! Platform::auth()->check()) || $this->getVerifiable()->hasVerifiedEmail()) {
            redirect()->intended(Platform::getUrl());
        }
    }

    protected function getVerifiable(): MustVerifyEmail
    {
        /** @var MustVerifyEmail $user */
        $user = Platform::auth()->user();

        return $user;
    }

    protected function sendEmailVerificationNotification(MustVerifyEmail $user): void
    {
        if ($user->hasVerifiedEmail()) {
            return;
        }

        if (! method_exists($user, 'notify')) {
            $userClass = $user::class;

            throw new LogicException("Model [{$userClass}] does not have a [notify()] method.");
        }

        $notification = app(VerifyEmail::class);
        $notification->url = Platform::getVerifyEmailUrl($user);

        $user->notify($notification);
    }

    public function resendNotificationAction(): Action
    {
        return Action::make('resendNotification')
            ->link()
            ->label(__('webkernel-platform::auth/pages/email-verification/email-verification-prompt.actions.resend_notification.label') . '.')
            ->size('sm')
            ->action(function (): void {
                try {
                    $this->rateLimit(2);
                } catch (TooManyRequestsException $exception) {
                    $this->getRateLimitedNotification($exception)?->send();

                    return;
                }

                $rateLimitingKey = 'platform:auth:resend_email_verification:rate_limit:' . Platform::getUserScopedAuthIdentifier();

                if (RateLimiter::tooManyAttempts($rateLimitingKey, maxAttempts: 2)) {
                    $this->getRateLimitedNotification(new TooManyRequestsException(
                        static::class,
                        'resendNotification',
                        request()->ip(),
                        RateLimiter::availableIn($rateLimitingKey),
                    ))?->send();

                    return;
                }

                RateLimiter::hit($rateLimitingKey);

                $this->sendEmailVerificationNotification($this->getVerifiable());

                Notification::make()
                    ->title(__('webkernel-platform::auth/pages/email-verification/email-verification-prompt.notifications.notification_resent.title'))
                    ->success()
                    ->send();
            });
    }

    protected function getRateLimitedNotification(TooManyRequestsException $exception): ?Notification
    {
        return Notification::make()
            ->title(__('webkernel-platform::auth/pages/email-verification/email-verification-prompt.notifications.notification_resend_throttled.title', [
                'seconds' => $exception->secondsUntilAvailable,
                'minutes' => $exception->minutesUntilAvailable,
            ]))
            ->body(array_key_exists('body', __('webkernel-platform::auth/pages/email-verification/email-verification-prompt.notifications.notification_resend_throttled') ?: []) ? __('webkernel-platform::auth/pages/email-verification/email-verification-prompt.notifications.notification_resend_throttled.body', [
                'seconds' => $exception->secondsUntilAvailable,
                'minutes' => $exception->minutesUntilAvailable,
            ]) : null)
            ->danger();
    }

    public function getTitle(): string | Htmlable
    {
        return __('webkernel-platform::auth/pages/email-verification/email-verification-prompt.title');
    }

    public function getHeading(): string | Htmlable | null
    {
        return __('webkernel-platform::auth/pages/email-verification/email-verification-prompt.heading');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Text::make(__('webkernel-platform::auth/pages/email-verification/email-verification-prompt.messages.notification_sent', [
                    'email' => platform()->auth()->user()->getEmailForVerification(),
                ])),
                Text::make(new HtmlString(
                    __('webkernel-platform::auth/pages/email-verification/email-verification-prompt.messages.notification_not_received') .
                    ' ' .
                    $this->resendNotificationAction->toHtml(),
                )),
            ]);
    }
}
