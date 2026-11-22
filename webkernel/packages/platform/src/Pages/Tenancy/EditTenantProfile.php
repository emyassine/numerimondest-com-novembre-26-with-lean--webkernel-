<?php

namespace Webkernel\Platform\Pages\Tenancy;

use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Actions\ActionGroup;
use Webkernel\Platform\Facades\Platform;
use Webkernel\Platform\Notifications\Notification;
use Webkernel\Platform\Pages\Concerns;
use Webkernel\Platform\Pages\Page;
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
use Webkernel\Liveview\Attributes\Locked;
use Throwable;

use function Webkernel\Platform\authorize;

/**
 * @property-read Schema $form
 */
abstract class EditTenantProfile extends Page
{
    use Concerns\CanUseDatabaseTransactions;
    use Concerns\HasRoutes;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    /**
     * @var ?Model
     */
    #[Locked]
    public $tenant = null;

    protected static bool $isDiscovered = false;

    abstract public static function getLabel(): string;

    public static function getRelativeRouteName(Panel $panel): string
    {
        return 'profile';
    }

    public static function getRouteName(?Panel $panel = null): string
    {
        $panel ??= Platform::getCurrentOrDefaultPanel();

        return $panel->generateRouteName('tenant.' . static::getRelativeRouteName($panel));
    }

    public static function isTenantSubscriptionRequired(Panel $panel): bool
    {
        return false;
    }

    public function mount(): void
    {
        $this->tenant = Platform::getTenant();

        abort_unless(static::canView($this->tenant), 404);

        $this->fillForm();
    }

    public function hydrate(): void
    {
        abort_unless(static::canView($this->tenant), 404);
    }

    public function resolveScopedModelProperties(): void
    {
        $this->tenant = Platform::getTenant();
    }

    protected function fillForm(): void
    {
        $data = $this->tenant->attributesToArray();

        $this->callHook('beforeFill');

        $data = $this->mutateFormDataBeforeFill($data);

        $this->form->fill($data);

        $this->callHook('afterFill');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $data;
    }

    public function save(): void
    {
        try {
            $this->beginDatabaseTransaction();

            $this->callHook('beforeValidate');

            $data = $this->form->getState();

            $this->callHook('afterValidate');

            $data = $this->mutateFormDataBeforeSave($data);

            $this->callHook('beforeSave');

            $this->handleRecordUpdate($this->tenant, $data);

            $this->callHook('afterSave');
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

        $this->getSavedNotification()?->send();

        if ($redirectUrl = $this->getRedirectUrl()) {
            $this->redirect($redirectUrl, navigate: PlatformView::hasSpaMode($redirectUrl));
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->update($data);

        return $record;
    }

    protected function getSavedNotification(): ?Notification
    {
        $title = $this->getSavedNotificationTitle();

        if (blank($title)) {
            return null;
        }

        return Notification::make()
            ->success()
            ->title($title);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('webkernel-platform::pages/tenancy/edit-tenant-profile.notifications.saved.title');
    }

    protected function getRedirectUrl(): ?string
    {
        return null;
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->operation('edit')
            ->model($this->tenant)
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema;
    }

    /**
     * @return array<Action | ActionGroup>
     */
    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')
            ->label(__('webkernel-platform::pages/tenancy/edit-tenant-profile.form.actions.save.label'))
            ->submit('save')
            ->keyBindings(['mod+s']);
    }

    public function getTitle(): string | Htmlable
    {
        return static::getLabel();
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return static::$slug ?? 'profile';
    }

    public static function canView(Model $tenant): bool
    {
        try {
            return authorize('update', $tenant)->allowed();
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
            ->livewireSubmitHandler('save')
            ->footer([
                Actions::make($this->getFormActions())
                    ->alignment($this->getFormActionsAlignment())
                    ->fullWidth($this->hasFullWidthFormActions())
                    ->sticky($this->areFormActionsSticky())
                    ->key('form-actions'),
            ]);
    }

    protected function hasFullWidthFormActions(): bool
    {
        return false;
    }
}
