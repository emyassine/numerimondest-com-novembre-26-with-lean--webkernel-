<?php

namespace Webkernel\Platform\Auth\Pages;

use Webkernel\Liveview\Features\SupportRateLimiting\Exceptions\TooManyRequestsException;
use Webkernel\Liveview\Features\SupportRateLimiting\WithRateLimiting;
use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Actions\ActionGroup;
use Webkernel\Platform\Auth\Http\Responses\Contracts\LoginResponse;
use Webkernel\Platform\Auth\MultiFactor\MultiFactorChallenge;
use Webkernel\Platform\Facades\Platform;
use Webkernel\Platform\Forms\Components\Checkbox;
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
use Webkernel\Platform\Support\Enums\Alignment;
use Webkernel\Platform\View\PanelsRenderHook;
use \Webkernel\Accounts\Events\Attempting;
use \Webkernel\Accounts\Events\Failed;
use \Webkernel\Accounts\SessionGuard;
use \Webkernel\Accounts\Contracts\Authenticatable;
use \Webkernel\Accounts\Contracts\Guard;
use Webkernel\View\Htmlable;
use Webkernel\Utilities\Arr;
use Webkernel\View\HtmlString;
use Webkernel\View\View as WebkernelView;
use Webkernel\Utilities\Timebox;
use \Webkernel\Validation\ValidationException;
use Webkernel\Liveview\Attributes\Locked;
use SensitiveParameter;

/**
 * @property-read Action $registerAction
 * @property-read Schema $form
 * @property-read Schema $multiFactorChallengeForm
 */
class Login extends SimplePage
{
    use RestrictsFileUploadsToSchemaComponents;
    use WithRateLimiting;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    #[Locked]
    public ?string $userUndertakingMultiFactorAuthentication = null;

    public function mount(): void
    {
        if (Platform::auth()->check()) {
            redirect()->intended(Platform::getUrl());
        }

        $this->form->fill();
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();

        /** @var SessionGuard $authGuard */
        $authGuard = Platform::auth();

        $authProvider = $authGuard->getProvider(); /** @phpstan-ignore-line */
        $credentials = $this->getCredentialsFromFormData($data);
        $remember = $data['remember'] ?? false;
        $timeboxDuration = (int) config('auth.timebox_duration', 200_000);

        $user = app(Timebox::class)->call(function (Timebox $timebox) use ($authProvider, $authGuard, $credentials, $remember): Authenticatable {
            $this->fireAttemptingEvent($authGuard, $credentials, $remember);

            $user = $authProvider->retrieveByCredentials($credentials);

            if ((! $user) || (! $authProvider->validateCredentials($user, $credentials))) {
                $this->userUndertakingMultiFactorAuthentication = null;

                $this->fireFailedEvent($authGuard, $user, $credentials);
                $this->throwFailureValidationException();
            }

            // This must run before the multi-factor challenge is presented, otherwise
            // the challenge confirms that the password was valid for an account that
            // can never sign in. It must also stay inside the `Timebox`, so that the
            // failure is padded to the same duration as an invalid password.
            if (! $this->isUserAllowedToAccessPanel($user)) {
                $this->userUndertakingMultiFactorAuthentication = null;

                $this->fireFailedEvent($authGuard, $user, $credentials);
                $this->throwFailureValidationException();
            }

            $timebox->returnEarly();

            return $user;
        }, $timeboxDuration);

        $needsMultiFactorChallenge = app(Timebox::class)->call(function (Timebox $timebox) use ($user): bool {
            $userUndertakingMultiFactorAuthenticationData = $this->getUserUndertakingMultiFactorAuthenticationData();

            if (
                $userUndertakingMultiFactorAuthenticationData &&
                hash_equals($userUndertakingMultiFactorAuthenticationData['userKey'], Platform::getUserScopedAuthIdentifier($user)) &&
                $this->getUserUndertakingMultiFactorAuthentication()
            ) {
                if ($this->isMultiFactorChallengeRateLimited($user)) {
                    return true;
                }

                $this->cacheSchema('multiFactorChallengeForm', null);
                $this->multiFactorChallengeForm->validate();

                return false;
            }

            $this->userUndertakingMultiFactorAuthentication = null;

            $multiFactorChallenge = $this->getMultiFactorChallenge();

            if ($multiFactorAuthenticationProvider = $multiFactorChallenge->getFirstEnabledProvider($user)) {
                $this->userUndertakingMultiFactorAuthentication = encrypt([
                    'identifier' => $user->getAuthIdentifier(),
                    'userKey' => Platform::getUserScopedAuthIdentifier($user),
                ]);

                $multiFactorChallenge->beforeChallenge($user, $multiFactorAuthenticationProvider);
            }

            if (filled($this->userUndertakingMultiFactorAuthentication)) {
                $this->cacheSchema('multiFactorChallengeForm', null);
                $this->multiFactorChallengeForm->fill();

                return true;
            }

            if (Platform::getMultiFactorAuthenticationProviders() === []) {
                $timebox->returnEarly();
            }

            return false;
        }, $timeboxDuration);

        if ($needsMultiFactorChallenge) {
            return null;
        }

        $userScopedAuthIdentifier = Platform::getUserScopedAuthIdentifier($user);

        // Credentials are deliberately validated again after the multi-factor challenge so that
        // password and panel access changes made during the challenge are observed before login.
        // The corresponding second `Attempting` event is intentional.
        if (! $authGuard->attemptWhen(
            $credentials,
            fn (Authenticatable $user): bool => hash_equals($userScopedAuthIdentifier, Platform::getUserScopedAuthIdentifier($user)) && $this->isUserAllowedToAccessPanel($user),
            $remember,
        )) {
            $this->throwFailureValidationException();
        }

        session()->regenerate();

        return app(LoginResponse::class);
    }

    protected function isUserAllowedToAccessPanel(Authenticatable $user): bool
    {
        if (! ($user instanceof PlatformUser)) {
            return true;
        }

        return $user->canAccessPanel(Platform::getCurrentOrDefaultPanel());
    }

    protected function getMultiFactorChallenge(): MultiFactorChallenge
    {
        return MultiFactorChallenge::make();
    }

    protected function isMultiFactorChallengeRateLimited(Authenticatable $user): bool
    {
        $multiFactorChallenge = $this->getMultiFactorChallenge();

        if ($multiFactorChallenge->isRateLimited($user)) {
            $this->getRateLimitedNotification(new TooManyRequestsException(
                static::class,
                'authenticate',
                request()->ip(),
                $multiFactorChallenge->getRateLimiterAvailableInSeconds($user),
            ))?->send();

            return true;
        }

        $multiFactorChallenge->hitRateLimiter($user);

        return false;
    }

    protected function getRateLimitedNotification(TooManyRequestsException $exception): ?Notification
    {
        return Notification::make()
            ->title(__('webkernel-platform::auth/pages/login.notifications.throttled.title', [
                'seconds' => $exception->secondsUntilAvailable,
                'minutes' => $exception->minutesUntilAvailable,
            ]))
            ->body(array_key_exists('body', __('webkernel-platform::auth/pages/login.notifications.throttled') ?: []) ? __('webkernel-platform::auth/pages/login.notifications.throttled.body', [
                'seconds' => $exception->secondsUntilAvailable,
                'minutes' => $exception->minutesUntilAvailable,
            ]) : null)
            ->danger();
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    protected function fireAttemptingEvent(Guard $guard, #[SensitiveParameter] array $credentials, bool $remember): void
    {
        event(app(Attempting::class, ['guard' => property_exists($guard, 'name') ? $guard->name : '', 'credentials' => $credentials, 'remember' => $remember]));
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    protected function fireFailedEvent(Guard $guard, ?Authenticatable $user, #[SensitiveParameter] array $credentials): void
    {
        event(app(Failed::class, ['guard' => property_exists($guard, 'name') ? $guard->name : '', 'user' => $user, 'credentials' => $credentials]));
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.email' => __('webkernel-platform::auth/pages/login.messages.failed'),
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
            ]);
    }

    public function defaultMultiFactorChallengeForm(Schema $schema): Schema
    {
        return $schema
            ->components(function (): array {
                $user = $this->getUserUndertakingMultiFactorAuthentication();

                if (! $user) {
                    return [];
                }

                return [
                    ...Arr::wrap($this->getMultiFactorProviderFormComponent()),
                    ...$this->getMultiFactorChallenge()->getChallengeSchemaComponents($user),
                ];
            })
            ->statePath('data.multiFactor');
    }

    protected function getUserUndertakingMultiFactorAuthentication(): ?Authenticatable
    {
        $data = $this->getUserUndertakingMultiFactorAuthenticationData();

        if (! $data) {
            return null;
        }

        $authProvider = Platform::auth()->getProvider(); /** @phpstan-ignore-line */
        $user = $authProvider->retrieveById($data['identifier']);

        if ((! $user) || (! hash_equals($data['userKey'], Platform::getUserScopedAuthIdentifier($user)))) {
            return null;
        }

        return $user;
    }

    /**
     * @return array{identifier: mixed, userKey: string} | null
     */
    protected function getUserUndertakingMultiFactorAuthenticationData(): ?array
    {
        if (blank($this->userUndertakingMultiFactorAuthentication)) {
            return null;
        }

        $data = decrypt($this->userUndertakingMultiFactorAuthentication);

        if (
            (! is_array($data))
            || (! array_key_exists('identifier', $data))
            || (! is_string($data['userKey'] ?? null))
        ) {
            return null;
        }

        return $data;
    }

    public function multiFactorChallengeForm(Schema $schema): Schema
    {
        return $schema;
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label(__('webkernel-platform::auth/pages/login.form.email.label'))
            ->email()
            ->required()
            ->autocomplete()
            ->autofocus();
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('webkernel-platform::auth/pages/login.form.password.label'))
            ->hint(platform()->hasPasswordReset() ? WebkernelView::html(WebkernelView::renderString('<x-webkernel-platform::link :href="platform()->getRequestPasswordResetUrl()" tabindex="-1"> {{ __(\'webkernel-platform::auth/pages/login.actions.request_password_reset.label\') }}</x-webkernel-platform::link>')) : null)
            ->password()
            ->revealable(platform()->arePasswordsRevealable())
            ->autocomplete('current-password')
            ->required();
    }

    protected function getRememberFormComponent(): Component
    {
        return Checkbox::make('remember')
            ->label(__('webkernel-platform::auth/pages/login.form.remember.label'));
    }

    protected function getMultiFactorProviderFormComponent(): ?Component
    {
        $user = $this->getUserUndertakingMultiFactorAuthentication();

        if (! $user) {
            return null;
        }

        return $this->getMultiFactorChallenge()->getProviderPickerSchemaComponent($user);
    }

    public function registerAction(): Action
    {
        return Action::make('register')
            ->link()
            ->label(__('webkernel-platform::auth/pages/login.actions.register.label'))
            ->url(platform()->getRegistrationUrl());
    }

    public function getTitle(): string | Htmlable
    {
        return __('webkernel-platform::auth/pages/login.title');
    }

    public function getHeading(): string | Htmlable | null
    {
        if (filled($this->userUndertakingMultiFactorAuthentication)) {
            return __('webkernel-platform::auth/pages/login.multi_factor.heading');
        }

        return __('webkernel-platform::auth/pages/login.heading');
    }

    /**
     * @return array<Action | ActionGroup>
     */
    protected function getFormActions(): array
    {
        return [
            $this->getAuthenticateFormAction(),
        ];
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->label(__('webkernel-platform::auth/pages/login.form.actions.authenticate.label'))
            ->submit('authenticate');
    }

    /**
     * @return array<Action | ActionGroup>
     */
    protected function getMultiFactorChallengeFormActions(): array
    {
        return [
            $this->getMultiFactorAuthenticateFormAction(),
        ];
    }

    protected function getMultiFactorAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->label(__('webkernel-platform::auth/pages/login.multi_factor.form.actions.authenticate.label'))
            ->submit('authenticate');
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }

    protected function hasFullWidthMultiFactorChallengeFormActions(): bool
    {
        return $this->hasFullWidthFormActions();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        return [
            'email' => $data['email'],
            'password' => $data['password'],
        ];
    }

    public function getSubheading(): string | Htmlable | null
    {
        if (filled($this->userUndertakingMultiFactorAuthentication)) {
            return __('webkernel-platform::auth/pages/login.multi_factor.subheading');
        }

        if (! platform()->hasRegistration()) {
            return null;
        }

        return new HtmlString(__('webkernel-platform::auth/pages/login.actions.register.before') . ' ' . $this->registerAction->toHtml());
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                RenderHook::make(PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE),
                $this->getFormContentComponent(),
                $this->getMultiFactorChallengeFormContentComponent(),
                RenderHook::make(PanelsRenderHook::AUTH_LOGIN_FORM_AFTER),
            ]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('authenticate')
            ->footer([
                Actions::make($this->getFormActions())
                    ->alignment($this->getFormActionsAlignment())
                    ->fullWidth($this->hasFullWidthFormActions())
                    ->key('form-actions'),
            ])
            ->visible(fn (): bool => blank($this->userUndertakingMultiFactorAuthentication));
    }

    public function getMultiFactorChallengeFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('multiFactorChallengeForm')])
            ->id('multiFactorChallengeForm')
            ->livewireSubmitHandler('authenticate')
            ->footer([
                Actions::make($this->getMultiFactorChallengeFormActions())
                    ->alignment($this->getMultiFactorChallengeFormActionsAlignment())
                    ->fullWidth($this->hasFullWidthMultiFactorChallengeFormActions()),
            ])
            ->visible(fn (): bool => filled($this->userUndertakingMultiFactorAuthentication));
    }

    public function getMultiFactorChallengeFormActionsAlignment(): string | Alignment
    {
        return $this->getFormActionsAlignment();
    }
}
