<?php

namespace Webkernel\Platform\Auth\MultiFactor\App\Actions;

use Closure;
use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Actions\Contracts\HasActions;
use Webkernel\Platform\Auth\MultiFactor\App\AppAuthentication;
use Webkernel\Platform\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Webkernel\Platform\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Webkernel\Platform\Facades\Platform;
use Webkernel\Platform\Forms\Components\OneTimeCodeInput;
use Webkernel\Platform\Forms\Components\TextInput;
use Webkernel\Platform\Notifications\Notification;
use Webkernel\Platform\Schemas\Components\Component;
use Webkernel\Platform\Schemas\Components\Flex;
use Webkernel\Platform\Schemas\Components\Group;
use Webkernel\Platform\Schemas\Components\Image;
use Webkernel\Platform\Schemas\Components\Text;
use Webkernel\Platform\Schemas\Components\UnorderedList;
use Webkernel\Platform\Schemas\Components\Wizard;
use Webkernel\Platform\Schemas\Components\Wizard\Step;
use Webkernel\Platform\Schemas\Contracts\HasSchemas;
use Webkernel\Platform\Schemas\Schema;
use Webkernel\Platform\Support\Enums\FontFamily;
use Webkernel\Platform\Support\Enums\FontWeight;
use Webkernel\Platform\Support\Enums\TextSize;
use Webkernel\Platform\Support\Enums\Width;
use Webkernel\View\View as WebkernelView;
use Webkernel\Platform\Support\Facades\PlatformIcon;
use Webkernel\Platform\Support\Icons\Heroicon;
use Webkernel\Platform\View\PanelsIconAlias;
use \Webkernel\Accounts\Contracts\Authenticatable;
use Webkernel\View\Htmlable;
use \Webkernel\Database\Database as DB;
use Webkernel\Liveview\Features\SupportRateLimiting\RateLimiter;
use Webkernel\View\HtmlString;
use Webkernel\View\Js;
use \Webkernel\Validation\ValidationException;
use LogicException;
use SensitiveParameter;

class SetUpAppAuthenticationAction
{
    public static function make(AppAuthentication $appAuthentication): Action
    {
        $rateLimitAuthenticationAttempt = static function (string $passwordStatePath): void {
            $rateLimitingKey = 'platform:auth:set_up_app_authentication:rate_limit:' . Platform::getUserScopedAuthIdentifier();

            if (RateLimiter::tooManyAttempts($rateLimitingKey, maxAttempts: 5)) {
                throw ValidationException::withMessages([
                    $passwordStatePath => __('webkernel-platform::auth/multi-factor/app/actions/set-up.modal.form.code.messages.rate_limited'),
                ]);
            }

            RateLimiter::hit($rateLimitingKey);
        };

        $getPasswordInput = static function (Schema $schema): TextInput {
            $passwordInput = $schema->getComponent('password');

            if (! $passwordInput instanceof TextInput) {
                throw new LogicException('The password input could not be found in the app authentication setup schema.');
            }

            return $passwordInput;
        };

        $passwordInput = TextInput::make('password')
            ->label(__('webkernel-platform::auth/multi-factor/app/actions/set-up.modal.form.password.label'))
            ->validationAttribute(__('webkernel-platform::auth/multi-factor/app/actions/set-up.modal.form.password.validation_attribute'))
            ->currentPassword(guard: Platform::getAuthGuard())
            ->password()
            ->revealable(Platform::arePasswordsRevealable())
            ->required()
            ->dehydrated(false);

        return Action::make('setUpAppAuthentication')
            ->label(__('webkernel-platform::auth/multi-factor/app/actions/set-up.label'))
            ->color('primary')
            ->icon(PlatformIcon::resolve(PanelsIconAlias::AUTH_MULTI_FACTOR_APP_ACTIONS_SET_UP) ?? Heroicon::LockClosed)
            ->link()
            ->mountUsing(function (HasActions $livewire, Schema $schema) use ($appAuthentication): void {
                $schema->fill();

                $livewire->mergeMountedActionArguments([
                    'encrypted' => encrypt([
                        'secret' => $appAuthentication->generateSecret(),
                        ...($appAuthentication->isRecoverable()
                            ? ['recoveryCodes' => $appAuthentication->generateRecoveryCodes()]
                            : []),
                        'userKey' => Platform::getUserScopedAuthIdentifier(),
                    ]),
                ]);
            })
            ->modalWidth(Width::Large)
            ->closeModalByClickingAway(false)
            ->closeModalByEscaping(false)
            ->modalIcon(PlatformIcon::resolve(PanelsIconAlias::AUTH_MULTI_FACTOR_APP_ACTIONS_SET_UP_MODAL) ?? Heroicon::OutlinedLockClosed)
            ->modalIconColor('primary')
            ->modalHeading(__('webkernel-platform::auth/multi-factor/app/actions/set-up.modal.heading'))
            ->modalDescription(WebkernelView::html(WebkernelView::renderString(__('webkernel-platform::auth/multi-factor/app/actions/set-up.modal.description'))))
            ->modifyWizardUsing(fn (Wizard $wizard) => $wizard->hiddenHeader())
            ->steps(fn (Action $action): array => [
                Step::make('app')
                    ->beforeValidation(fn (Step $component) => $rateLimitAuthenticationAttempt($getPasswordInput($component->getChildSchema())->getStatePath()))
                    ->schema([
                        Group::make([
                            Text::make(__('webkernel-platform::auth/multi-factor/app/actions/set-up.modal.content.qr_code.instruction'))
                                ->color('neutral'),
                            Image::make(
                                url: fn (): string => $appAuthentication->generateQrCodeDataUri(decrypt($action->getArguments()['encrypted'])['secret']),
                                alt: __('webkernel-platform::auth/multi-factor/app/actions/set-up.modal.content.qr_code.alt'),
                            )
                                ->imageHeight('12rem')
                                ->alignCenter(),
                            Flex::make([
                                Text::make(__('webkernel-platform::auth/multi-factor/app/actions/set-up.modal.content.text_code.instruction'))
                                    ->color('neutral')
                                    ->grow(false),
                                Text::make(fn (): string => decrypt($action->getArguments()['encrypted'])['secret'])
                                    ->fontFamily(FontFamily::Mono)
                                    ->color('neutral')
                                    ->copyable()
                                    ->copyMessage(__('webkernel-platform::auth/multi-factor/app/actions/set-up.modal.content.text_code.messages.copied'))
                                    ->grow(false),
                            ])->from('sm'),
                        ])
                            ->dense(),
                        OneTimeCodeInput::make('code')
                            ->label(__('webkernel-platform::auth/multi-factor/app/actions/set-up.modal.form.code.label'))
                            ->belowContent(__('webkernel-platform::auth/multi-factor/app/actions/set-up.modal.form.code.below_content'))
                            ->validationAttribute(__('webkernel-platform::auth/multi-factor/app/actions/set-up.modal.form.code.validation_attribute'))
                            ->required()
                            ->rule(function () use ($action, $appAuthentication): Closure {
                                return function (string $attribute, #[SensitiveParameter] $value, Closure $fail) use ($action, $appAuthentication): void {
                                    if ($appAuthentication->verifyCode($value, decrypt($action->getArguments()['encrypted'])['secret'])) {
                                        return;
                                    }

                                    $fail(__('webkernel-platform::auth/multi-factor/app/actions/set-up.modal.form.code.messages.invalid'));
                                };
                            }),
                        $passwordInput,
                    ]),
                Step::make('recovery')
                    ->schema([
                        Text::make(__('webkernel-platform::auth/multi-factor/app/actions/set-up.modal.content.recovery_codes.instruction'))
                            ->weight(FontWeight::Bold)
                            ->color('neutral'),
                        UnorderedList::make(fn (): array => array_map(
                            fn (string $recoveryCode): Component => Text::make($recoveryCode)
                                ->fontFamily(FontFamily::Mono)
                                ->size(TextSize::ExtraSmall)
                                ->color('neutral'),
                            decrypt($action->getArguments()['encrypted'] ?? encrypt([]))['recoveryCodes'] ?? [],
                        ))
                            ->size(TextSize::ExtraSmall),
                        Text::make(function () use ($action): Htmlable {
                            $recoveryCodes = decrypt($action->getArguments()['encrypted'])['recoveryCodes'];

                            return new HtmlString(
                                __('webkernel-platform::auth/multi-factor/recovery-codes-modal-content.actions.0') .
                                ' ' .
                                Action::make('copy')
                                    ->label(__('webkernel-platform::auth/multi-factor/recovery-codes-modal-content.actions.copy.label'))
                                    ->link()
                                    ->alpineClickHandler('
                                                    window.navigator.clipboard.writeText(' . Js::from(implode(PHP_EOL, $recoveryCodes)) . ')
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
                                    ->url('data:application/octet-stream,' . urlencode(implode(PHP_EOL, $recoveryCodes)))
                                    ->extraAttributes(['download' => 'recovery-codes.txt'])
                                    ->toHtml() .
                                ' ' .
                                __('webkernel-platform::auth/multi-factor/recovery-codes-modal-content.actions.2')
                            );
                        }),
                    ])
                    ->visible($appAuthentication->isRecoverable()),
            ])
            ->beforeFormValidated(function (HasActions & HasSchemas $livewire) use ($getPasswordInput, $rateLimitAuthenticationAttempt): void {
                $mountedActionSchemaName = $livewire->getMountedActionSchemaName();

                if ($mountedActionSchemaName === null) {
                    throw new LogicException('The mounted app authentication setup schema could not be found.');
                }

                $mountedActionSchema = $livewire->getSchema($mountedActionSchemaName);

                if ($mountedActionSchema === null) {
                    throw new LogicException('The mounted app authentication setup schema could not be found.');
                }

                $rateLimitAuthenticationAttempt($getPasswordInput($mountedActionSchema)->getStatePath());
            })
            ->modalSubmitAction(fn (Action $action) => $action
                ->label(__('webkernel-platform::auth/multi-factor/app/actions/set-up.modal.actions.submit.label')))
            ->action(function (array $arguments) use ($appAuthentication): void {
                /** @var Authenticatable&HasAppAuthentication&HasAppAuthenticationRecovery $user */
                $user = Platform::auth()->user();

                $encrypted = decrypt($arguments['encrypted']);

                if (Platform::getUserScopedAuthIdentifier($user) !== $encrypted['userKey']) {
                    // Avoid encrypted arguments being passed between users by verifying that the authenticated
                    // user is the same as the user that the encrypted arguments were issued for.
                    return;
                }

                DB::transaction(function () use ($appAuthentication, $encrypted, $user): void {
                    $appAuthentication->saveSecret($user, $encrypted['secret']);

                    if ($appAuthentication->isRecoverable()) {
                        $appAuthentication->saveRecoveryCodes($user, $encrypted['recoveryCodes']);
                    }
                });

                Notification::make()
                    ->title(__('webkernel-platform::auth/multi-factor/app/actions/set-up.notifications.enabled.title'))
                    ->success()
                    ->icon(PlatformIcon::resolve(PanelsIconAlias::AUTH_MULTI_FACTOR_APP_ACTIONS_SET_UP_NOTIFICATION) ?? Heroicon::OutlinedLockClosed)
                    ->send();
            })
            ->rateLimit(5);
    }
}
