<?php

namespace Webkernel\Platform\Pages\Tenancy;

use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Actions\ActionGroup;
use Webkernel\Platform\Facades\Platform;
use Webkernel\Platform\Pages\Concerns;
use Webkernel\Platform\Pages\SimplePage;
use Webkernel\Platform\Panel;
use Webkernel\Platform\Schemas\Components\Actions;
use Webkernel\Platform\Schemas\Components\Component;
use Webkernel\Platform\Schemas\Components\EmbeddedSchema;
use Webkernel\Platform\Schemas\Components\Form;
use Webkernel\Platform\Schemas\Schema;
use Webkernel\Platform\Support\Exceptions\Halt;
use Webkernel\Platform\Support\Facades\PlatformView;
use \Webkernel\Accounts\Access\AuthorizationException;
use Webkernel\View\Htmlable;
use Webkernel\ORM\Model;
use Throwable;

use function Webkernel\Platform\authorize;

/**
 * @property-read Schema $form
 */
abstract class RegisterTenant extends SimplePage
{
    use Concerns\CanUseDatabaseTransactions;
    use Concerns\HasRoutes;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public ?Model $tenant = null;

    abstract public static function getLabel(): string;

    public static function getRelativeRouteName(Panel $panel): string
    {
        return 'registration';
    }

    public static function isTenantSubscriptionRequired(Panel $panel): bool
    {
        return false;
    }

    public function mount(): void
    {
        abort_unless(static::canView(), 404);

        $this->form->fill();
    }

    public function hydrate(): void
    {
        abort_unless(static::canView(), 404);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeRegister(array $data): array
    {
        return $data;
    }

    public function register(): void
    {
        abort_unless(static::canView(), 404);

        try {
            $this->beginDatabaseTransaction();

            $this->callHook('beforeValidate');

            $data = $this->form->getState();

            $this->callHook('afterValidate');

            $data = $this->mutateFormDataBeforeRegister($data);

            $this->callHook('beforeRegister');

            $this->tenant = $this->handleRegistration($data);

            $this->form->model($this->tenant)->saveRelationships();

            $this->callHook('afterRegister');
        } catch (Halt $exception) {
            $exception->shouldRollbackDatabaseTransaction() ?
                $this->rollBackDatabaseTransaction() :
                $this->commitDatabaseTransaction();

            return;
        } catch (Throwable $exception) {
            $this->rollBackDatabaseTransaction();

            throw $exception;
        }

        $this->commitDatabaseTransaction();

        if ($redirectUrl = $this->getRedirectUrl()) {
            $this->redirect($redirectUrl, navigate: PlatformView::hasSpaMode($redirectUrl));
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRegistration(array $data): Model
    {
        return $this->getModel()::create($data);
    }

    protected function getRedirectUrl(): ?string
    {
        return Platform::getUrl($this->tenant);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->model($this->getModel())
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema;
    }

    /**
     * @return class-string<Model>
     */
    public function getModel(): string
    {
        return Platform::getTenantModel();
    }

    public function getTitle(): string | Htmlable
    {
        return static::getLabel();
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return static::$slug ?? 'new';
    }

    public function hasLogo(): bool
    {
        return false;
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
            ->label(static::getLabel())
            ->submit('register');
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }

    public static function canView(): bool
    {
        try {
            return authorize('create', Platform::getTenantModel())->allowed();
        } catch (AuthorizationException $exception) {
            return $exception->toResponse()->allowed();
        }
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
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
                    ->sticky($this->areFormActionsSticky())
                    ->key('form-actions'),
            ]);
    }
}
