<?php

namespace Webkernel\Platform\Resources\Pages;

use BackedEnum;
use Closure;
use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Actions\ActionGroup;
use Webkernel\Platform\Actions\CreateAction;
use Webkernel\Platform\Actions\EditAction;
use Webkernel\Platform\Actions\ViewAction;
use Webkernel\Platform\Facades\Platform;
use Webkernel\Platform\Notifications\Notification;
use Webkernel\Platform\Pages\Concerns\CanUseDatabaseTransactions;
use Webkernel\Platform\Pages\Concerns\HasUnsavedDataChangesAlert;
use Webkernel\Platform\Resources\Events\RecordSaved;
use Webkernel\Platform\Resources\Events\RecordUpdated;
use Webkernel\Platform\Schemas\Components\Actions;
use Webkernel\Platform\Schemas\Components\Component;
use Webkernel\Platform\Schemas\Components\EmbeddedSchema;
use Webkernel\Platform\Schemas\Components\Form;
use Webkernel\Platform\Schemas\Components\Group;
use Webkernel\Platform\Schemas\Components\Tabs\Tab;
use Webkernel\Platform\Schemas\Schema;
use Webkernel\Platform\Support\Exceptions\Halt;
use Webkernel\Platform\Support\Facades\PlatformIcon;
use Webkernel\Platform\Support\Facades\PlatformView;
use Webkernel\Platform\Support\Icons\Heroicon;
use Webkernel\Platform\View\PanelsIconAlias;
use \Webkernel\Accounts\Access\AuthorizationException;
use Webkernel\View\Htmlable;
use Webkernel\ORM\Model;
use \Webkernel\Events\Event;
use Webkernel\View\Js;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * @template TModel of Model = Model
 *
 * @property-read Schema $form
 */
class EditRecord extends Page
{
    use CanUseDatabaseTransactions;
    use Concerns\HasRelationManagers {
        getContentTabComponent as getBaseContentTabComponent;
    }
    use Concerns\InteractsWithRecord {
        getRecord as getBaseRecord;
    }
    use HasUnsavedDataChangesAlert;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public ?string $previousUrl = null;

    public static function getNavigationIcon(): string | BackedEnum | Htmlable | null
    {
        return static::$navigationIcon
            ?? PlatformIcon::resolve(PanelsIconAlias::RESOURCES_PAGES_EDIT_RECORD_NAVIGATION_ITEM)
            ?? Heroicon::OutlinedPencilSquare;
    }

    public function getBreadcrumb(): string
    {
        return static::$breadcrumb ?? __('webkernel-platform::resources/pages/edit-record.breadcrumb');
    }

    public static function getNavigationLabel(): string | Htmlable
    {
        if (filled(static::$navigationLabel)) {
            return static::$navigationLabel;
        }

        return __('webkernel-platform::resources/pages/edit-record.navigation_label');
    }

    public function getContentTabLabel(): ?string
    {
        return __('webkernel-platform::resources/pages/edit-record.content.tab.label');
    }

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->authorizeAccess();

        $this->fillForm();

        $this->previousUrl = url()->previous();
    }

    protected function authorizeAccess(): void
    {
        abort_unless(static::getResource()::canEdit($this->getRecord()), 403);
    }

    public function hydrate(): void
    {
        $this->authorizeAccess();
    }

    protected function fillForm(): void
    {
        /** @internal Read the DocBlock above the following method. */
        $this->fillFormWithDataAndCallHooks($this->getRecord());
    }

    /**
     * @internal Never override or call this method. If you completely override `fillForm()`, copy the contents of this method into your override.
     *
     * @param  array<string, mixed>  $extraData
     */
    protected function fillFormWithDataAndCallHooks(Model $record, array $extraData = []): void
    {
        $this->callHook('beforeFill');

        $data = $this->mutateFormDataBeforeFill([
            ...$record->attributesToArray(),
            ...$extraData,
        ]);

        $this->form->fill($data);

        $this->callHook('afterFill');
    }

    /**
     * @param  array<string>  $statePaths
     */
    public function refreshFormData(array $statePaths): void
    {
        $this->form->fillPartially(
            $this->mutateFormDataBeforeFill($this->getRecord()->attributesToArray()),
            $statePaths,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Security: All non-`$hidden` model attributes are sent to the
        // browser via Livewire. Override this to `unset()` sensitive
        // attributes (API keys, internal flags, etc.). Only form
        // field attributes are writable — not a mass assignment
        // issue, but a data exposure concern.

        return $data;
    }

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        $this->authorizeAccess();

        try {
            $this->beginDatabaseTransaction();

            $this->callHook('beforeValidate');

            $data = $this->form->getState(afterValidate: function (): void {
                $this->callHook('afterValidate');

                $this->callHook('beforeSave');
            });

            $data = $this->mutateFormDataBeforeSave($data);

            $this->handleRecordUpdate($this->getRecord(), $data);

            $this->callHook('afterSave');
            Event::dispatch(RecordUpdated::class, ['record' => $this->record, 'data' => $data, 'page' => $this]);
            Event::dispatch(RecordSaved::class, ['record' => $this->record, 'data' => $data, 'page' => $this]);
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

        $this->rememberData();

        if ($shouldSendSavedNotification) {
            $this->getSavedNotification()?->send();
        }

        if ($shouldRedirect && ($redirectUrl = $this->getRedirectUrl())) {
            $this->redirect($redirectUrl, navigate: PlatformView::hasSpaMode($redirectUrl));
        }
    }

    public function saveFormComponentOnly(Component $component): void
    {
        $this->authorizeAccess();

        try {
            $this->beginDatabaseTransaction();

            $this->callHook('beforeValidate');

            $oldContainer = $component->getContainer();

            $data = Schema::make($component->getLivewire())
                ->components([$component])
                ->model($component->getRecord())
                ->operation($oldContainer->getOperation())
                ->statePath('data')
                ->getState();

            $component->container($oldContainer);

            $this->callHook('afterValidate');

            $data = $this->mutateFormDataBeforeSave($data);

            $this->callHook('beforeSave');

            $this->handleRecordUpdate($this->getRecord(), $data);

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

        $this->rememberData();
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
        return $this->getSavedNotificationMessage() ?? __('webkernel-platform::resources/pages/edit-record.notifications.saved.title');
    }

    /**
     * @deprecated Use `getSavedNotificationTitle()` instead.
     */
    protected function getSavedNotificationMessage(): ?string
    {
        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->update($data);

        return $record;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $data;
    }

    public function getDefaultActionSchemaResolver(Action $action): ?Closure
    {
        return match (true) {
            $action instanceof CreateAction => fn (Schema $schema): Schema => static::getResource()::form($schema->hasCustomColumns() ? $schema : $schema->columns(2)),
            $action instanceof EditAction => fn (Schema $schema): Schema => $schema->components([EmbeddedSchema::make('form')]),
            $action instanceof ViewAction => fn (Schema $schema): Schema => static::getResource()::infolist(static::getResource()::form($schema->hasCustomColumns() ? $schema : $schema->columns(2))),
            default => null,
        };
    }

    public function getTitle(): string | Htmlable
    {
        if (filled(static::$title)) {
            return static::$title;
        }

        return __('webkernel-platform::resources/pages/edit-record.title', [
            'label' => $this->getRecordTitle(),
        ]);
    }

    /**
     * @return array<Action | ActionGroup>
     */
    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        $hasFormWrapper = $this->hasFormWrapper();

        return Action::make('save')
            ->label(__('webkernel-platform::resources/pages/edit-record.form.actions.save.label'))
            ->submit($hasFormWrapper ? $this->getSubmitFormLivewireMethodName() : null)
            ->action($hasFormWrapper ? null : $this->getSubmitFormLivewireMethodName())
            ->keyBindings(['mod+s']);
    }

    protected function getSubmitFormAction(): Action
    {
        return $this->getSaveFormAction();
    }

    protected function getSubmitFormLivewireMethodName(): string
    {
        return 'save';
    }

    protected function getCancelFormAction(): Action
    {
        $url = $this->previousUrl ?? $this->getResourceUrl();

        return Action::make('cancel')
            ->label(__('webkernel-platform::resources/pages/edit-record.form.actions.cancel.label'))
            ->alpineClickHandler(
                PlatformView::hasSpaMode($url)
                    ? 'document.referrer ? window.history.back() : Livewire.navigate(' . Js::from($url) . ')'
                    : 'document.referrer ? window.history.back() : (window.location.href = ' . Js::from($url) . ')',
            )
            ->color('gray');
    }

    public function defaultForm(Schema $schema): Schema
    {
        if (! $schema->hasCustomColumns()) {
            $schema->columns($this->hasInlineLabels() ? 1 : 2);
        }

        return $schema
            ->inlineLabel($this->hasInlineLabels())
            ->model($this->getRecord())
            ->operation('edit')
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return static::getResource()::form($schema);
    }

    public function getContentTabComponent(): Tab
    {
        return $this->getBaseContentTabComponent()
            ->schema([
                $this->getFormContentComponent(),
            ]);
    }

    protected function getRedirectUrl(): ?string
    {
        $resource = static::getResource();

        if (
            filled($defaultRedirect = Platform::getResourceEditPageRedirect()) &&
            $resource::hasPage($defaultRedirect) &&
            (($defaultRedirect !== 'view') || $resource::canView($this->getRecord()))
        ) {
            return $this->getResourceUrl($defaultRedirect, $this->getRedirectUrlParameters());
        }

        try {
            $this->authorizeAccess();

            return null;
        } catch (AuthorizationException | HttpExceptionInterface) {
            // Do nothing.
        }

        if ($resource::hasPage('view') && $resource::canView($this->getRecord())) {
            return $this->getResourceUrl('view', $this->getRedirectUrlParameters());
        }

        return $this->getResourceUrl(parameters: $this->getRedirectUrlParameters());
    }

    /**
     * @return array<string, mixed>
     */
    protected function getRedirectUrlParameters(): array
    {
        return [];
    }

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return parent::shouldRegisterNavigation($parameters) && static::getResource()::canEdit($parameters['record']);
    }

    public function content(Schema $schema): Schema
    {
        if ($this->hasCombinedRelationManagerTabsWithContent()) {
            return $schema
                ->components([
                    $this->getRelationManagersContentComponent(),
                ]);
        }

        return $schema
            ->components([
                $this->getFormContentComponent(),
                $this->getRelationManagersContentComponent(),
            ]);
    }

    public function getFormContentComponent(): Component
    {
        if (! $this->hasFormWrapper()) {
            return Group::make([
                EmbeddedSchema::make('form'),
                $this->getFormActionsContentComponent(),
            ]);
        }

        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler($this->getSubmitFormLivewireMethodName())
            ->footer([
                $this->getFormActionsContentComponent(),
            ]);
    }

    public function getFormActionsContentComponent(): Component
    {
        return Actions::make($this->getFormActions())
            ->alignment($this->getFormActionsAlignment())
            ->fullWidth($this->hasFullWidthFormActions())
            ->sticky($this->areFormActionsSticky())
            ->key('form-actions');
    }

    public function hasFormWrapper(): bool
    {
        return true;
    }

    /**
     * @return array<string>
     */
    public function getPageClasses(): array
    {
        return [
            'fi-resource-edit-record-page',
            'fi-resource-' . str_replace('/', '-', $this->getResource()::getSlug(Platform::getCurrentOrDefaultPanel())),
            "fi-resource-record-{$this->getRecord()->getKey()}",
        ];
    }

    protected function hasFullWidthFormActions(): bool
    {
        return false;
    }

    /**
     * @return TModel
     */
    public function getRecord(): Model
    {
        return $this->getBaseRecord();
    }
}
