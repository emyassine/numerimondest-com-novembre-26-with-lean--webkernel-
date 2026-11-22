<?php

namespace Webkernel\Platform\Auth\Pages\PasswordReset;

use Webkernel\Liveview\Features\SupportRateLimiting\Exceptions\TooManyRequestsException;
use Webkernel\Liveview\Features\SupportRateLimiting\WithRateLimiting;
use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Actions\ActionGroup;
use Webkernel\Platform\Auth\Http\Responses\Contracts\PasswordResetResponse;
use Webkernel\Platform\Facades\Platform;
use Webkernel\Platform\Forms\Components\TextInput;
use Webkernel\Platform\Models\Contracts\PlatformUser;
use Webkernel\Platform\Notifications\Notification;
use Webkernel\Platform\Pages\SimplePage;
use Webkernel\Platform\Schemas\Components\Actions;
use Webkernel\Platform\Schemas\Components\Component;
use Webkernel\Platform\Schemas\Components\EmbeddedSchema;
use Webkernel\Platform\Schemas\Components\Form;
use Webkernel\Platform\Schemas\Components\RenderHook;
use Webkernel\Platform\Schemas\Concerns\RestrictsFileUploadsToSchemaComponents;
use Webkernel\Platform\Schemas\Schema;
use Webkernel\Platform\View\PanelsRenderHook;
use \Webkernel\Accounts\Events\PasswordReset;
use \Webkernel\Accounts\Contracts\Authenticatable;
use \Webkernel\Accounts\Contracts\CanResetPassword;
use Webkernel\View\Htmlable;
use Webkernel\ORM\Model;
use \Webkernel\Cryptography\Hash;
use \Webkernel\Accounts\Password;
use Webkernel\Liveview\Features\SupportRateLimiting\RateLimiter;
use Webkernel\Utilities\Str;
use \Webkernel\Validation\Rules\Password as PasswordRule;
use Webkernel\Liveview\Attributes\Locked;
use SensitiveParameter;

use function Webkernel\Platform\Support\encode_key_segments;

/**
 * @property-read Schema $form
 */
class ResetPassword extends SimplePage
{
    use RestrictsFileUploadsToSchemaComponents;
    use WithRateLimiting;

    #[Locked]
    public ?string $email = null;

    public ?string $password = '';

    public ?string $passwordConfirmation = '';

    #[Locked]
    public ?string $token = null;

    public function mount(?string $email = null, #[SensitiveParameter] ?string $token = null): void
    {
        if (Platform::auth()->check()) {
            redirect()->intended(Platform::getUrl());
        }

        $this->token = $token ?? request()->query('token');

        $this->form->fill([
            'email' => $email ?? request()->query('email'),
        ]);
    }

    public function resetPassword(): ?PasswordResetResponse
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        if ($this->isResetPasswordRateLimited($this->email)) {
            return null;
        }

        $data = $this->form->getState();

        $data['email'] = $this->email;
        $data['token'] = $this->token;

        $hasPanelAccess = true;

        $status = Password::broker(Platform::getAuthPasswordBroker())->reset(
            $this->getCredentialsFromFormData($data),
            function (CanResetPassword | Model | Authenticatable $user) use ($data, &$hasPanelAccess): void {
                if (
                    ($user instanceof PlatformUser) &&
                    (! $user->canAccessPanel(Platform::getCurrentOrDefaultPanel()))
                ) {
                    $hasPanelAccess = false;

                    return;
                }

                $user->forceFill([
                    $user->getAuthPasswordName() => Hash::make($data['password']),
                    $user->getRememberTokenName() => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($hasPanelAccess === false) {
            $status = Password::INVALID_USER;
        }

        if ($status === Password::PASSWORD_RESET) {
            Notification::make()
                ->title(__($status))
                ->success()
                ->send();

            return app(PasswordResetResponse::class);
        }

        Notification::make()
            ->title(__($status))
            ->danger()
            ->send();

        return null;
    }

    protected function getRateLimitedNotification(TooManyRequestsException $exception): ?Notification
    {
        return Notification::make()
            ->title(__('webkernel-platform::auth/pages/password-reset/reset-password.notifications.throttled.title', [
                'seconds' => $exception->secondsUntilAvailable,
                'minutes' => $exception->minutesUntilAvailable,
            ]))
            ->body(array_key_exists('body', __('webkernel-platform::auth/pages/password-reset/reset-password.notifications.throttled') ?: []) ? __('webkernel-platform::auth/pages/password-reset/reset-password.notifications.throttled.body', [
                'seconds' => $exception->secondsUntilAvailable,
                'minutes' => $exception->minutesUntilAvailable,
            ]) : null)
            ->danger();
    }

    protected function isResetPasswordRateLimited(?string $email): bool
    {
        if (blank($email)) {
            return false;
        }

        $passwordBroker = Platform::getAuthPasswordBroker() ?? Password::getDefaultDriver();
        $rateLimitingKey = 'platform:auth:reset_password:rate_limit:' . hash('sha256', encode_key_segments($passwordBroker, $email));

        if (RateLimiter::tooManyAttempts($rateLimitingKey, maxAttempts: 2)) {
            $this->getRateLimitedNotification(new TooManyRequestsException(
                static::class,
                'resetPassword',
                request()->ip(),
                RateLimiter::availableIn($rateLimitingKey),
            ))?->send();

            return true;
        }

        RateLimiter::hit($rateLimitingKey);

        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label(__('webkernel-platform::auth/pages/password-reset/reset-password.form.email.label'))
            ->disabled()
            ->autofocus();
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('webkernel-platform::auth/pages/password-reset/reset-password.form.password.label'))
            ->password()
            ->autocomplete('new-password')
            ->revealable(platform()->arePasswordsRevealable())
            ->required()
            ->rule(PasswordRule::default())
            ->same('passwordConfirmation')
            ->validationAttribute(__('webkernel-platform::auth/pages/password-reset/reset-password.form.password.validation_attribute'));
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return TextInput::make('passwordConfirmation')
            ->label(__('webkernel-platform::auth/pages/password-reset/reset-password.form.password_confirmation.label'))
            ->password()
            ->autocomplete('new-password')
            ->revealable(platform()->arePasswordsRevealable())
            ->required()
            ->dehydrated(false);
    }

    public function getTitle(): string | Htmlable
    {
        return __('webkernel-platform::auth/pages/password-reset/reset-password.title');
    }

    public function getHeading(): string | Htmlable | null
    {
        return __('webkernel-platform::auth/pages/password-reset/reset-password.heading');
    }

    /**
     * @return array<Action | ActionGroup>
     */
    protected function getFormActions(): array
    {
        return [
            $this->getResetPasswordFormAction(),
        ];
    }

    public function getResetPasswordFormAction(): Action
    {
        return Action::make('resetPassword')
            ->label(__('webkernel-platform::auth/pages/password-reset/reset-password.form.actions.reset.label'))
            ->submit('resetPassword');
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                RenderHook::make(PanelsRenderHook::AUTH_PASSWORD_RESET_RESET_FORM_BEFORE),
                $this->getFormContentComponent(),
                RenderHook::make(PanelsRenderHook::AUTH_PASSWORD_RESET_RESET_FORM_AFTER),
            ]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('resetPassword')
            ->footer([
                Actions::make($this->getFormActions())
                    ->alignment($this->getFormActionsAlignment())
                    ->fullWidth($this->hasFullWidthFormActions())
                    ->key('form-actions'),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        return $data;
    }
}
