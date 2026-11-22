<?php

namespace Webkernel\Platform\Auth\Pages\PasswordReset;

use Webkernel\Liveview\Features\SupportRateLimiting\Exceptions\TooManyRequestsException;
use Webkernel\Liveview\Features\SupportRateLimiting\WithRateLimiting;
use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Actions\ActionGroup;
use Webkernel\Platform\Auth\Notifications\ResetPassword as ResetPasswordNotification;
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
use Webkernel\Platform\Support\Facades\PlatformIcon;
use Webkernel\Platform\Support\Icons\Heroicon;
use Webkernel\Platform\View\PanelsIconAlias;
use Webkernel\Platform\View\PanelsRenderHook;
use \Webkernel\Accounts\Events\PasswordResetLinkSent;
use \Webkernel\Accounts\Contracts\CanResetPassword;
use Webkernel\View\Htmlable;
use \Webkernel\Accounts\Password;
use LogicException;
use SensitiveParameter;

/**
 * @property-read Action $loginAction
 * @property-read Schema $form
 */
class RequestPasswordReset extends SimplePage
{
    use RestrictsFileUploadsToSchemaComponents;
    use WithRateLimiting;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public function mount(): void
    {
        if (Platform::auth()->check()) {
            redirect()->intended(Platform::getUrl());
        }

        $this->form->fill();
    }

    public function request(): void
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return;
        }

        $data = $this->form->getState();

        $status = Password::broker(Platform::getAuthPasswordBroker())->sendResetLink(
            $this->getCredentialsFromFormData($data),
            function (CanResetPassword $user, #[SensitiveParameter] string $token): void {
                if (
                    ($user instanceof PlatformUser) &&
                    (! $user->canAccessPanel(Platform::getCurrentOrDefaultPanel()))
                ) {
                    return;
                }

                if (! method_exists($user, 'notify')) {
                    $userClass = $user::class;

                    throw new LogicException("Model [{$userClass}] does not have a [notify()] method.");
                }

                $notification = app(ResetPasswordNotification::class, ['token' => $token]);
                $notification->url = Platform::getResetPasswordUrl($token, $user);

                $user->notify($notification);

                if (class_exists(PasswordResetLinkSent::class)) {
                    event(new PasswordResetLinkSent($user));
                }
            },
        );

        if ($status !== Password::RESET_LINK_SENT) {
            $this->getFailureNotification($status)?->send();

            return;
        }

        $this->getSentNotification($status)?->send();

        $this->form->fill();
    }

    protected function getRateLimitedNotification(TooManyRequestsException $exception): ?Notification
    {
        return Notification::make()
            ->title(__('webkernel-platform::auth/pages/password-reset/request-password-reset.notifications.throttled.title', [
                'seconds' => $exception->secondsUntilAvailable,
                'minutes' => $exception->minutesUntilAvailable,
            ]))
            ->body(array_key_exists('body', __('webkernel-platform::auth/pages/password-reset/request-password-reset.notifications.throttled') ?: []) ? __('webkernel-platform::auth/pages/password-reset/request-password-reset.notifications.throttled.body', [
                'seconds' => $exception->secondsUntilAvailable,
                'minutes' => $exception->minutesUntilAvailable,
            ]) : null)
            ->danger();
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('data');
    }

    protected function getFailureNotification(string $status): ?Notification
    {
        return Notification::make()
            ->title(__($status))
            ->danger();
    }

    protected function getSentNotification(string $status): ?Notification
    {
        return Notification::make()
            ->title(__($status))
            ->body(($status === Password::RESET_LINK_SENT) ? __('webkernel-platform::auth/pages/password-reset/request-password-reset.notifications.sent.body') : null)
            ->success();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getEmailFormComponent(),
            ]);
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label(__('webkernel-platform::auth/pages/password-reset/request-password-reset.form.email.label'))
            ->email()
            ->required()
            ->autocomplete()
            ->autofocus();
    }

    public function loginAction(): Action
    {
        return Action::make('login')
            ->link()
            ->label(__('webkernel-platform::auth/pages/password-reset/request-password-reset.actions.login.label'))
            ->icon(match (__('webkernel-platform::layout.direction')) {
                'rtl' => PlatformIcon::resolve(PanelsIconAlias::PAGES_PASSWORD_RESET_REQUEST_PASSWORD_RESET_ACTIONS_LOGIN_RTL) ?? Heroicon::ArrowRight,
                default => PlatformIcon::resolve(PanelsIconAlias::PAGES_PASSWORD_RESET_REQUEST_PASSWORD_RESET_ACTIONS_LOGIN) ?? Heroicon::ArrowLeft,
            })
            ->url(platform()->getLoginUrl());
    }

    public function getTitle(): string | Htmlable
    {
        return __('webkernel-platform::auth/pages/password-reset/request-password-reset.title');
    }

    public function getHeading(): string | Htmlable | null
    {
        return __('webkernel-platform::auth/pages/password-reset/request-password-reset.heading');
    }

    /**
     * @return array<Action | ActionGroup>
     */
    protected function getFormActions(): array
    {
        return [
            $this->getRequestFormAction(),
        ];
    }

    protected function getRequestFormAction(): Action
    {
        return Action::make('request')
            ->label(__('webkernel-platform::auth/pages/password-reset/request-password-reset.form.actions.request.label'))
            ->submit('request');
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }

    public function getSubheading(): string | Htmlable | null
    {
        if (! platform()->hasLogin()) {
            return null;
        }

        return $this->loginAction;
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                RenderHook::make(PanelsRenderHook::AUTH_PASSWORD_RESET_REQUEST_FORM_BEFORE),
                $this->getFormContentComponent(),
                RenderHook::make(PanelsRenderHook::AUTH_PASSWORD_RESET_REQUEST_FORM_AFTER),
            ]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('request')
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
    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'email' => $data['email'],
        ];
    }
}
