<?php

namespace Webkernel\Platform\Auth\Pages;

use Webkernel\Liveview\Features\SupportRateLimiting\Exceptions\TooManyRequestsException;
use Webkernel\Liveview\Features\SupportRateLimiting\WithRateLimiting;
use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Actions\ActionGroup;
use Webkernel\Platform\Auth\Events\Registered;
use Webkernel\Platform\Auth\Http\Responses\Contracts\RegistrationResponse;
use Webkernel\Platform\Auth\Notifications\VerifyEmail;
use Webkernel\Platform\Facades\Platform;
use Webkernel\Platform\Forms\Components\TextInput;
use Webkernel\Platform\Notifications\Notification;
use Webkernel\Platform\Pages\Concerns\CanUseDatabaseTransactions;
use Webkernel\Platform\Pages\SimplePage;
use Webkernel\Platform\Schemas\Components\Actions;
use Webkernel\Platform\Schemas\Components\Component;
use Webkernel\Platform\Schemas\Components\EmbeddedSchema;
use Webkernel\Platform\Schemas\Components\Form;
use Webkernel\Platform\Schemas\Components\RenderHook;
use Webkernel\Platform\Schemas\Concerns\RestrictsFileUploadsToSchemaComponents;
use Webkernel\Platform\Schemas\Schema;
use Webkernel\Platform\View\PanelsRenderHook;
use \Webkernel\Accounts\EloquentUserProvider;
use \Webkernel\Accounts\SessionGuard;
use \Webkernel\Accounts\Contracts\MustVerifyEmail;
use Webkernel\View\Htmlable;
use Webkernel\ORM\Model;
use \Webkernel\Cryptography\Hash;
use Webkernel\Liveview\Features\SupportRateLimiting\RateLimiter;
use Webkernel\View\HtmlString;
use \Webkernel\Validation\Rules\Password;
use LogicException;
use SensitiveParameter;

use function Webkernel\Platform\Support\encode_key_segments;

/**
 * @property-read Action $loginAction
 * @property-read Schema $form
 */
class Register extends SimplePage
{
    use CanUseDatabaseTransactions;
    use RestrictsFileUploadsToSchemaComponents;
    use WithRateLimiting;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    /**
     * @var class-string<Model>
     */
    protected string $userModel;

    public function mount(): void
    {
        if (Platform::auth()->check()) {
            redirect()->intended(Platform::getUrl());
        }

        $this->callHook('beforeFill');

        $this->form->fill();

        $this->callHook('afterFill');
    }

    public function register(): ?RegistrationResponse
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        if ($this->isRegisterRateLimited($this->data['email'] ?? '')) {
            return null;
        }

        $user = $this->wrapInDatabaseTransaction(function (): Model {
            $this->callHook('beforeValidate');

            $data = $this->form->getState();

            $this->callHook('afterValidate');

            $data = $this->mutateFormDataBeforeRegister($data);

            $this->callHook('beforeRegister');

            $user = $this->handleRegistration($data);

            $this->form->model($user)->saveRelationships();

            $this->callHook('afterRegister');

            return $user;
        });

        event(new Registered($user));

        $this->sendEmailVerificationNotification($user);

        Platform::auth()->login($user);

        session()->regenerate();

        return app(RegistrationResponse::class);
    }

    protected function getRateLimitedNotification(TooManyRequestsException $exception): ?Notification
    {
        return Notification::make()
            ->title(__('webkernel-platform::auth/pages/register.notifications.throttled.title', [
                'seconds' => $exception->secondsUntilAvailable,
                'minutes' => $exception->minutesUntilAvailable,
            ]))
            ->body(array_key_exists('body', __('webkernel-platform::auth/pages/register.notifications.throttled') ?: []) ? __('webkernel-platform::auth/pages/register.notifications.throttled.body', [
                'seconds' => $exception->secondsUntilAvailable,
                'minutes' => $exception->minutesUntilAvailable,
            ]) : null)
            ->danger();
    }

    protected function isRegisterRateLimited(string $email): bool
    {
        if (blank($email)) {
            return false;
        }

        $rateLimitingKey = 'platform:auth:register:rate_limit:' . hash('sha256', encode_key_segments(Platform::getAuthGuard(), $this->getUserModel(), $email));

        if (RateLimiter::tooManyAttempts($rateLimitingKey, maxAttempts: 2)) {
            $this->getRateLimitedNotification(new TooManyRequestsException(
                static::class,
                'register',
                request()->ip(),
                RateLimiter::availableIn($rateLimitingKey),
            ))?->send();

            return true;
        }

        RateLimiter::hit($rateLimitingKey);

        return false;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRegistration(#[SensitiveParameter] array $data): Model
    {
        return $this->getUserModel()::create($data);
    }

    protected function sendEmailVerificationNotification(Model $user): void
    {
        if (! $user instanceof MustVerifyEmail) {
            return;
        }

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

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }

    protected function getNameFormComponent(): Component
    {
        return TextInput::make('name')
            ->label(__('webkernel-platform::auth/pages/register.form.name.label'))
            ->required()
            ->maxLength(255)
            ->autofocus();
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label(__('webkernel-platform::auth/pages/register.form.email.label'))
            ->email()
            ->required()
            ->maxLength(255)
            ->unique($this->getUserModel());
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('webkernel-platform::auth/pages/register.form.password.label'))
            ->password()
            ->revealable(platform()->arePasswordsRevealable())
            ->required()
            ->rule(Password::default())
            ->showAllValidationMessages()
            ->dehydrateStateUsing(fn (#[SensitiveParameter] $state) => Hash::make($state))
            ->same('passwordConfirmation')
            ->validationAttribute(__('webkernel-platform::auth/pages/register.form.password.validation_attribute'));
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return TextInput::make('passwordConfirmation')
            ->label(__('webkernel-platform::auth/pages/register.form.password_confirmation.label'))
            ->password()
            ->revealable(platform()->arePasswordsRevealable())
            ->required()
            ->dehydrated(false);
    }

    public function loginAction(): Action
    {
        return Action::make('login')
            ->link()
            ->label(__('webkernel-platform::auth/pages/register.actions.login.label'))
            ->url(platform()->getLoginUrl());
    }

    /**
     * @return class-string<Model>
     */
    protected function getUserModel(): string
    {
        if (isset($this->userModel)) {
            return $this->userModel;
        }

        /** @var SessionGuard $authGuard */
        $authGuard = Platform::auth();

        /** @var EloquentUserProvider $provider */
        $provider = $authGuard->getProvider();

        return $this->userModel = $provider->getModel();
    }

    public function getTitle(): string | Htmlable
    {
        return __('webkernel-platform::auth/pages/register.title');
    }

    public function getHeading(): string | Htmlable | null
    {
        return __('webkernel-platform::auth/pages/register.heading');
    }

    /**
     * @return array<Action | ActionGroup>
     */
    protected function getFormActions(): array
    {
        return [
            $this->getRegisterFormAction(),
        ];
    }

    public function getRegisterFormAction(): Action
    {
        return Action::make('register')
            ->label(__('webkernel-platform::auth/pages/register.form.actions.register.label'))
            ->submit('register');
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeRegister(#[SensitiveParameter] array $data): array
    {
        return $data;
    }

    public function getSubheading(): string | Htmlable | null
    {
        if (! platform()->hasLogin()) {
            return null;
        }

        return new HtmlString(__('webkernel-platform::auth/pages/register.actions.login.before') . ' ' . $this->loginAction->toHtml());
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                RenderHook::make(PanelsRenderHook::AUTH_REGISTER_FORM_BEFORE),
                $this->getFormContentComponent(),
                RenderHook::make(PanelsRenderHook::AUTH_REGISTER_FORM_AFTER),
            ]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('register')
            ->footer([
                Actions::make($this->getFormActions())
                    ->alignment($this->getFormActionsAlignment())
                    ->fullWidth($this->hasFullWidthFormActions())
                    ->key('form-actions'),
            ]);
    }
}
