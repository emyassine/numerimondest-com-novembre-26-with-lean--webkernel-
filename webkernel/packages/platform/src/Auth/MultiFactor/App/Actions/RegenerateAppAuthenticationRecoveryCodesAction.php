<?php

namespace Webkernel\Platform\Auth\MultiFactor\App\Actions;

use Closure;
use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Actions\Contracts\HasActions;
use Webkernel\Platform\Auth\MultiFactor\App\AppAuthentication;
use Webkernel\Platform\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Webkernel\Platform\Facades\Platform;
use Webkernel\Platform\Forms\Components\OneTimeCodeInput;
use Webkernel\Platform\Forms\Components\TextInput;
use Webkernel\Platform\Notifications\Notification;
use Webkernel\Platform\Schemas\Components\Component;
use Webkernel\Platform\Schemas\Components\Group;
use Webkernel\Platform\Schemas\Components\Text;
use Webkernel\Platform\Schemas\Components\UnorderedList;
use Webkernel\Platform\Support\Enums\FontFamily;
use Webkernel\Platform\Support\Enums\Width;
use Webkernel\Platform\Support\Facades\PlatformIcon;
use Webkernel\Platform\Support\Icons\Heroicon;
use Webkernel\Platform\View\PanelsIconAlias;
use Webkernel\View\Htmlable;
use Webkernel\Liveview\Features\SupportRateLimiting\RateLimiter;
use Webkernel\View\HtmlString;
use Webkernel\View\Js;
use \Webkernel\Validation\ValidationException;
use Webkernel\Liveview\Component as LivewireComponent;
use SensitiveParameter;

class RegenerateAppAuthenticationRecoveryCodesAction
{
    public static function make(AppAuthentication $appAuthentication): Action
    {
        $rateLimitAuthenticationAttempt = static function (string $passwordStatePath): void {
            $rateLimitingKey = 'platform:auth:regenerate_recovery_codes:rate_limit:' . Platform::getUserScopedAuthIdentifier();

            if (RateLimiter::tooManyAttempts($rateLimitingKey, maxAttempts: 5)) {
                throw ValidationException::withMessages([
                    $passwordStatePath => __('webkernel-platform::auth/multi-factor/app/actions/regenerate-recovery-codes.modal.form.code.messages.rate_limited'),
                ]);
            }

            RateLimiter::hit($rateLimitingKey);
        };

        $passwordInput = TextInput::make('password')
            ->label(__('webkernel-platform::auth/multi-factor/app/actions/regenerate-recovery-codes.modal.form.password.label'))
            ->validationAttribute(__('webkernel-platform::auth/multi-factor/app/actions/regenerate-recovery-codes.modal.form.password.validation_attribute'))
            ->currentPassword(guard: Platform::getAuthGuard())
            ->password()
            ->revealable(platform()->arePasswordsRevealable())
            ->required()
            ->dehydrated(false);

        return Action::make('regenerateAppAuthenticationRecoveryCodes')
            ->label(__('webkernel-platform::auth/multi-factor/app/actions/regenerate-recovery-codes.label'))
            ->color('gray')
            ->icon(PlatformIcon::resolve(PanelsIconAlias::AUTH_MULTI_FACTOR_APP_ACTIONS_REGENERATE_RECOVERY_CODES) ?? Heroicon::ArrowPath)
            ->link()
            ->modalWidth(Width::Large)
            ->modalIcon(PlatformIcon::resolve(PanelsIconAlias::AUTH_MULTI_FACTOR_APP_ACTIONS_REGENERATE_RECOVERY_CODES_MODAL) ?? Heroicon::OutlinedArrowPath)
            ->modalIconColor('primary')
            ->modalHeading(__('webkernel-platform::auth/multi-factor/app/actions/regenerate-recovery-codes.modal.heading'))
            ->modalDescription(__('webkernel-platform::auth/multi-factor/app/actions/regenerate-recovery-codes.modal.description'))
            ->schema([
                OneTimeCodeInput::make('code')
                    ->label(__('webkernel-platform::auth/multi-factor/app/actions/regenerate-recovery-codes.modal.form.code.label'))
                    ->validationAttribute(__('webkernel-platform::auth/multi-factor/app/actions/regenerate-recovery-codes.modal.form.code.validation_attribute'))
                    ->requiredWithout('password')
                    ->rule(function () use ($appAuthentication): Closure {
                        return function (string $attribute, #[SensitiveParameter] $value, Closure $fail) use ($appAuthentication): void {
                            if ($appAuthentication->verifyCode($value, shouldPreventCodeReuse: true)) {
                                return;
                            }

                            $fail(__('webkernel-platform::auth/multi-factor/app/actions/regenerate-recovery-codes.modal.form.code.messages.invalid'));
                        };
                    }),
                $passwordInput,
            ])
            ->beforeFormValidated(function (LivewireComponent $livewire) use ($passwordInput, $rateLimitAuthenticationAttempt): void {
                $passwordStatePath = $passwordInput->getStatePath();

                $rateLimitAuthenticationAttempt($passwordStatePath);

                $livewire->validateOnly(
                    $passwordStatePath,
                    [$passwordStatePath => $passwordInput->getValidationRules()],
                    attributes: [$passwordStatePath => $passwordInput->getValidationAttribute()],
                );
            })
            ->modalSubmitAction(fn (Action $action) => $action
                ->label(__('webkernel-platform::auth/multi-factor/app/actions/regenerate-recovery-codes.modal.actions.submit.label'))
                ->color('danger'))
            ->action(function (Action $action, HasActions $livewire) use ($appAuthentication): void {
                $recoveryCodes = $appAuthentication->generateRecoveryCodes();

                /** @var HasAppAuthenticationRecovery $user */
                $user = Platform::auth()->user();

                $appAuthentication->saveRecoveryCodes($user, $recoveryCodes);

                $livewire->mountAction('showNewRecoveryCodes', arguments: [
                    'recoveryCodes' => $recoveryCodes,
                ]);

                Notification::make()
                    ->title(__('webkernel-platform::auth/multi-factor/app/actions/regenerate-recovery-codes.notifications.regenerated.title'))
                    ->success()
                    ->icon(PlatformIcon::resolve(PanelsIconAlias::AUTH_MULTI_FACTOR_APP_ACTIONS_REGENERATE_RECOVERY_CODES_NOTIFICATION) ?? Heroicon::OutlinedArrowPath)
                    ->send();
            })
            ->registerModalActions([
                Action::make('showNewRecoveryCodes')
                    ->modalHeading(__('webkernel-platform::auth/multi-factor/app/actions/regenerate-recovery-codes.show_new_recovery_codes.modal.heading'))
                    ->modalDescription(__('webkernel-platform::auth/multi-factor/app/actions/regenerate-recovery-codes.show_new_recovery_codes.modal.description'))
                    ->schema(fn (array $arguments) => [
                        Group::make([
                            UnorderedList::make(fn (): array => array_map(
                                fn (string $recoveryCode): Component => Text::make($recoveryCode)
                                    ->fontFamily(FontFamily::Mono)
                                    ->size('xs')
                                    ->color('neutral'),
                                $arguments['recoveryCodes'],
                            ))
                                ->size('xs'),
                            Text::make(fn (): Htmlable => new HtmlString(
                                __('webkernel-platform::auth/multi-factor/recovery-codes-modal-content.actions.0') .
                                ' ' .
                                Action::make('copy')
                                    ->label(__('webkernel-platform::auth/multi-factor/recovery-codes-modal-content.actions.copy.label'))
                                    ->link()
                                    ->alpineClickHandler('
                                        window.navigator.clipboard.writeText(' . Js::from(implode(PHP_EOL, $arguments['recoveryCodes'])) . ')
                                        $tooltip(' . Js::from(__('webkernel-platform::auth/multi-factor/recovery-codes-modal-content.messages.copied')) . ', {
                                            theme: $store.theme,
                                        })
                                    ')
                                    ->toHtml() .
                                ' ' .
                                __('webkernel-platform::auth/multi-factor/recovery-codes-modal-content.actions.1') .
                                ' ' .
                                Action::make('download')
                                    ->label(__('webkernel-platform::auth/multi-factor/recovery-codes-modal-content.actions.download.label'))
                                    ->link()
                                    ->url('data:application/octet-stream,' . urlencode(implode(PHP_EOL, $arguments['recoveryCodes'])))
                                    ->extraAttributes(['download' => 'recovery-codes.txt'])
                                    ->toHtml() .
                                ' ' .
                                __('webkernel-platform::auth/multi-factor/recovery-codes-modal-content.actions.2')
                            )),
                        ])
                            ->dense(),
                    ])
                    ->modalWidth(Width::Large)
                    ->closeModalByClickingAway(false)
                    ->closeModalByEscaping(false)
                    ->modalCloseButton(false)
                    ->modalSubmitAction(fn (Action $action) => $action
                        ->label(__('webkernel-platform::auth/multi-factor/app/actions/regenerate-recovery-codes.show_new_recovery_codes.modal.actions.submit.label'))
                        ->color('danger'))
                    ->modalCancelAction(false)
                    ->cancelParentActions(),
            ])
            ->rateLimit(5);
    }
}
