<?php

namespace Webkernel\Platform\Auth\MultiFactor\Email\Actions;

use Closure;
use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Webkernel\Platform\Auth\MultiFactor\Email\EmailAuthentication;
use Webkernel\Platform\Facades\Platform;
use Webkernel\Platform\Forms\Components\OneTimeCodeInput;
use Webkernel\Platform\Notifications\Notification;
use Webkernel\Platform\Schemas\Schema;
use Webkernel\Platform\Support\Enums\Width;
use Webkernel\Platform\Support\Facades\PlatformIcon;
use Webkernel\Platform\Support\Icons\Heroicon;
use Webkernel\Platform\View\PanelsIconAlias;
use \Webkernel\Accounts\Contracts\Authenticatable;
use \Webkernel\Database\Database as DB;
use Webkernel\Liveview\Features\SupportRateLimiting\RateLimiter;
use \Webkernel\Validation\ValidationException;
use SensitiveParameter;

class SetUpEmailAuthenticationAction
{
    public static function make(EmailAuthentication $emailAuthentication): Action
    {
        $rateLimitAuthenticationAttempt = static function (string $codeStatePath): void {
            $rateLimitingKey = 'platform:auth:set_up_email_authentication:rate_limit:' . Platform::getUserScopedAuthIdentifier();

            if (RateLimiter::tooManyAttempts($rateLimitingKey, maxAttempts: 5)) {
                throw ValidationException::withMessages([
                    $codeStatePath => __('webkernel-platform::auth/multi-factor/email/actions/set-up.modal.form.code.messages.rate_limited'),
                ]);
            }

            RateLimiter::hit($rateLimitingKey);
        };

        $codeInput = OneTimeCodeInput::make('code')
            ->label(__('webkernel-platform::auth/multi-factor/email/actions/set-up.modal.form.code.label'))
            ->belowContent(Action::make('resend')
                ->label(__('webkernel-platform::auth/multi-factor/email/actions/set-up.modal.form.code.actions.resend.label'))
                ->link()
                ->action(function () use ($emailAuthentication): void {
                    /** @var HasEmailAuthentication $user */
                    $user = Platform::auth()->user();

                    if (! $emailAuthentication->sendCode($user)) {
                        Notification::make()
                            ->title(__('webkernel-platform::auth/multi-factor/email/actions/set-up.modal.form.code.actions.resend.notifications.throttled.title'))
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title(__('webkernel-platform::auth/multi-factor/email/actions/set-up.modal.form.code.actions.resend.notifications.resent.title'))
                        ->success()
                        ->send();
                }))
            ->validationAttribute(__('webkernel-platform::auth/multi-factor/email/actions/set-up.modal.form.code.validation_attribute'))
            ->required()
            ->rule(function () use ($emailAuthentication): Closure {
                return function (string $attribute, #[SensitiveParameter] $value, Closure $fail) use ($emailAuthentication): void {
                    if ($emailAuthentication->verifyCode($value)) {
                        return;
                    }

                    $fail(__('webkernel-platform::auth/multi-factor/email/actions/set-up.modal.form.code.messages.invalid'));
                };
            });

        return Action::make('setUpEmailAuthentication')
            ->label(__('webkernel-platform::auth/multi-factor/email/actions/set-up.label'))
            ->color('primary')
            ->icon(PlatformIcon::resolve(PanelsIconAlias::AUTH_MULTI_FACTOR_EMAIL_ACTIONS_SET_UP) ?? Heroicon::LockClosed)
            ->link()
            ->mountUsing(function (Schema $schema) use ($emailAuthentication): void {
                $schema->fill();

                /** @var HasEmailAuthentication $user */
                $user = Platform::auth()->user();

                $emailAuthentication->sendCode($user);
            })
            ->modalWidth(Width::Large)
            ->modalIcon(PlatformIcon::resolve(PanelsIconAlias::AUTH_MULTI_FACTOR_EMAIL_ACTIONS_SET_UP_MODAL) ?? Heroicon::OutlinedLockClosed)
            ->modalIconColor('primary')
            ->modalHeading(__('webkernel-platform::auth/multi-factor/email/actions/set-up.modal.heading'))
            ->modalDescription(__('webkernel-platform::auth/multi-factor/email/actions/set-up.modal.description'))
            ->schema([
                $codeInput,
            ])
            ->beforeFormValidated(fn () => $rateLimitAuthenticationAttempt($codeInput->getStatePath()))
            ->modalSubmitAction(fn (Action $action) => $action
                ->label(__('webkernel-platform::auth/multi-factor/email/actions/set-up.modal.actions.submit.label')))
            ->action(function (): void {
                /** @var Authenticatable&HasEmailAuthentication $user */
                $user = Platform::auth()->user();

                DB::transaction(function () use ($user): void {
                    $user->toggleEmailAuthentication(true);
                });

                Notification::make()
                    ->title(__('webkernel-platform::auth/multi-factor/email/actions/set-up.notifications.enabled.title'))
                    ->success()
                    ->icon(PlatformIcon::resolve(PanelsIconAlias::AUTH_MULTI_FACTOR_EMAIL_ACTIONS_SET_UP_NOTIFICATION) ?? Heroicon::OutlinedLockClosed)
                    ->send();
            })
            ->rateLimit(5);
    }
}
