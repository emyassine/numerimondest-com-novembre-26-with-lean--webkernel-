<?php

namespace Webkernel\Platform\Auth\MultiFactor\App\Actions;

use Closure;
use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Auth\MultiFactor\App\AppAuthentication;
use Webkernel\Platform\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Webkernel\Platform\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Webkernel\Platform\Facades\Platform;
use Webkernel\Platform\Forms\Components\OneTimeCodeInput;
use Webkernel\Platform\Forms\Components\TextInput;
use Webkernel\Platform\Notifications\Notification;
use Webkernel\Platform\Schemas\Components\Utilities\Get;
use Webkernel\Platform\Schemas\Components\Utilities\Set;
use Webkernel\Platform\Support\Enums\Width;
use Webkernel\Platform\Support\Facades\PlatformIcon;
use Webkernel\Platform\Support\Icons\Heroicon;
use Webkernel\Platform\View\PanelsIconAlias;
use \Webkernel\Database\Database as DB;
use Webkernel\Liveview\Features\SupportRateLimiting\RateLimiter;
use \Webkernel\Validation\ValidationException;
use Webkernel\Liveview\Component;
use SensitiveParameter;

class DisableAppAuthenticationAction
{
    public static function make(AppAuthentication $appAuthentication): Action
    {
        $isRecoverable = $appAuthentication->isRecoverable();
        $rateLimitAuthenticationAttempt = static function (string $passwordStatePath): void {
            $rateLimitingKey = 'platform:auth:disable_app_authentication:rate_limit:' . Platform::getUserScopedAuthIdentifier();

            if (RateLimiter::tooManyAttempts($rateLimitingKey, maxAttempts: 5)) {
                throw ValidationException::withMessages([
                    $passwordStatePath => __('webkernel-platform::auth/multi-factor/app/actions/disable.modal.form.code.messages.rate_limited'),
                ]);
            }

            RateLimiter::hit($rateLimitingKey);
        };

        $passwordInput = TextInput::make('password')
            ->label(__('webkernel-platform::auth/multi-factor/app/actions/disable.modal.form.password.label'))
            ->validationAttribute(__('webkernel-platform::auth/multi-factor/app/actions/disable.modal.form.password.validation_attribute'))
            ->currentPassword(guard: Platform::getAuthGuard())
            ->password()
            ->revealable(Platform::arePasswordsRevealable())
            ->required()
            ->dehydrated(false);

        return Action::make('disableAppAuthentication')
            ->label(__('webkernel-platform::auth/multi-factor/app/actions/disable.label'))
            ->color('danger')
            ->icon(PlatformIcon::resolve(PanelsIconAlias::AUTH_MULTI_FACTOR_APP_ACTIONS_DISABLE) ?? Heroicon::LockOpen)
            ->link()
            ->modalWidth(Width::Medium)
            ->modalIcon(PlatformIcon::resolve(PanelsIconAlias::AUTH_MULTI_FACTOR_APP_ACTIONS_DISABLE_MODAL) ?? Heroicon::OutlinedLockOpen)
            ->modalHeading(__('webkernel-platform::auth/multi-factor/app/actions/disable.modal.heading'))
            ->modalDescription(__('webkernel-platform::auth/multi-factor/app/actions/disable.modal.description'))
            ->schema([
                $passwordInput,
                OneTimeCodeInput::make('code')
                    ->label(__('webkernel-platform::auth/multi-factor/app/actions/disable.modal.form.code.label'))
                    ->belowContent(fn (Get $get): Action => Action::make('useRecoveryCode')
                        ->label(__('webkernel-platform::auth/multi-factor/app/actions/disable.modal.form.code.actions.use_recovery_code.label'))
                        ->link()
                        ->action(fn (Set $set) => $set('useRecoveryCode', true))
                        ->visible(fn (): bool => $isRecoverable && (! $get('useRecoveryCode'))))
                    ->validationAttribute(__('webkernel-platform::auth/multi-factor/app/actions/disable.modal.form.code.validation_attribute'))
                    ->required(fn (Get $get): bool => (! $isRecoverable) || (! $get('useRecoveryCode')) || blank($get('recoveryCode')))
                    ->rule(function () use ($appAuthentication): Closure {
                        return function (string $attribute, #[SensitiveParameter] mixed $value, Closure $fail) use ($appAuthentication): void {
                            if (is_string($value) && $appAuthentication->verifyCode($value, shouldPreventCodeReuse: true)) {
                                return;
                            }

                            $fail(__('webkernel-platform::auth/multi-factor/app/actions/disable.modal.form.code.messages.invalid'));
                        };
                    }),
                TextInput::make('recoveryCode')
                    ->label(__('webkernel-platform::auth/multi-factor/app/actions/disable.modal.form.recovery_code.label'))
                    ->validationAttribute(__('webkernel-platform::auth/multi-factor/app/actions/disable.modal.form.recovery_code.validation_attribute'))
                    ->password()
                    ->revealable(Platform::arePasswordsRevealable())
                    ->autocomplete('one-time-code')
                    ->rule(function () use ($appAuthentication): Closure {
                        return function (string $attribute, #[SensitiveParameter] mixed $value, Closure $fail) use ($appAuthentication): void {
                            if (blank($value)) {
                                return;
                            }

                            if (is_string($value) && $appAuthentication->verifyRecoveryCode($value)) {
                                return;
                            }

                            $fail(__('webkernel-platform::auth/multi-factor/app/actions/disable.modal.form.recovery_code.messages.invalid'));
                        };
                    })
                    ->visible(fn (Get $get): bool => $isRecoverable && $get('useRecoveryCode'))
                    ->live(onBlur: true),
            ])
            ->beforeFormValidated(function (Component $livewire) use ($passwordInput, $rateLimitAuthenticationAttempt): void {
                $passwordStatePath = $passwordInput->getStatePath();

                $rateLimitAuthenticationAttempt($passwordStatePath);

                $livewire->validateOnly(
                    $passwordStatePath,
                    [$passwordStatePath => $passwordInput->getValidationRules()],
                    attributes: [$passwordStatePath => $passwordInput->getValidationAttribute()],
                );
            })
            ->modalSubmitAction(fn (Action $action) => $action
                ->label(__('webkernel-platform::auth/multi-factor/app/actions/disable.modal.actions.submit.label')))
            ->action(function () use ($appAuthentication, $isRecoverable): void {
                /** @var HasAppAuthentication&HasAppAuthenticationRecovery $user */
                $user = Platform::auth()->user();

                DB::transaction(function () use ($appAuthentication, $isRecoverable, $user): void {
                    $appAuthentication->saveSecret($user, null);

                    if ($isRecoverable) {
                        $appAuthentication->saveRecoveryCodes($user, null);
                    }
                });

                Notification::make()
                    ->title(__('webkernel-platform::auth/multi-factor/app/actions/disable.notifications.disabled.title'))
                    ->success()
                    ->icon(PlatformIcon::resolve(PanelsIconAlias::AUTH_MULTI_FACTOR_APP_ACTIONS_DISABLE_NOTIFICATION) ?? Heroicon::OutlinedLockOpen)
                    ->send();
            })
            ->rateLimit(5);
    }
}
