<?php

namespace Webkernel\Platform\Resources\RelationManagers;

use BackedEnum;
use Closure;
use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Actions\AssociateAction;
use Webkernel\Platform\Actions\AttachAction;
use Webkernel\Platform\Actions\Concerns\InteractsWithActions;
use Webkernel\Platform\Actions\Contracts\HasActions;
use Webkernel\Platform\Actions\CreateAction;
use Webkernel\Platform\Actions\DeleteAction;
use Webkernel\Platform\Actions\DeleteBulkAction;
use Webkernel\Platform\Actions\DetachAction;
use Webkernel\Platform\Actions\DetachBulkAction;
use Webkernel\Platform\Actions\DissociateAction;
use Webkernel\Platform\Actions\DissociateBulkAction;
use Webkernel\Platform\Actions\EditAction;
use Webkernel\Platform\Actions\ForceDeleteAction;
use Webkernel\Platform\Actions\ForceDeleteBulkAction;
use Webkernel\Platform\Actions\ImportAction;
use Webkernel\Platform\Actions\ReplicateAction;
use Webkernel\Platform\Actions\RestoreAction;
use Webkernel\Platform\Actions\RestoreBulkAction;
use Webkernel\Platform\Actions\ViewAction;
use Webkernel\Platform\Facades\Platform;
use Webkernel\Platform\Pages\Page;
use Webkernel\Platform\Resources\Concerns\InteractsWithRelationshipTable;
use Webkernel\Platform\Resources\Pages\ViewRecord;
use Webkernel\Platform\Resources\RelationManagers\Concerns\CanAuthorizeAccess;
use Webkernel\Platform\Schemas\Components\EmbeddedTable;
use Webkernel\Platform\Schemas\Components\RenderHook;
use Webkernel\Platform\Schemas\Components\Tabs\Tab;
use Webkernel\Platform\Schemas\Concerns\InteractsWithSchemas;
use Webkernel\Platform\Schemas\Contracts\HasRenderHookScopes;
use Webkernel\Platform\Schemas\Contracts\HasSchemas;
use Webkernel\Platform\Schemas\Schema;
use Webkernel\Platform\Support\Concerns\CanBeLazy;
use Webkernel\Platform\Support\Enums\IconPosition;
use Webkernel\Platform\Tables\Contracts\HasTable;
use Webkernel\Platform\Tables\Table;
use Webkernel\Platform\View\PanelsRenderHook;
use \Webkernel\Accounts\Access\AuthorizationException;
use \Webkernel\Accounts\Access\Response;
use Webkernel\View\Htmlable;
use Webkernel\View\Contracts\View;
use Webkernel\View\ViewResult;
use Webkernel\ORM\Model;
use Webkernel\Liveview\Attributes\Locked;
use Webkernel\Liveview\Component;

use function Webkernel\Platform\authorize;

class RelationManager extends Component implements HasActions, HasRenderHookScopes, HasSchemas, HasTable
{
    use CanAuthorizeAccess;
    use CanBeLazy;
    use InteractsWithActions;
    use InteractsWithRelationshipTable {
        InteractsWithRelationshipTable::makeTable as makeBaseRelationshipTable;
        InteractsWithRelationshipTable::canReorder as baseCanReorder;
    }
    use InteractsWithSchemas;

    /**
     * @var view-string
     */
    protected string $view = 'webkernel-platform::resources.relation-manager';

    #[Locked]
    public Model $ownerRecord;

    #[Locked]
    public ?string $pageClass = null;

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    protected static ?string $recordTitleAttribute = null;

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    protected static ?string $inverseRelationship = null;

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    protected static ?string $label = null;

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    protected static ?string $pluralLabel = null;

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    protected static ?string $modelLabel = null;

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    protected static ?string $pluralModelLabel = null;

    protected static ?string $title = null;

    protected static string | BackedEnum | null $icon = null;

    protected static IconPosition $iconPosition = IconPosition::Before;

    protected static ?string $badge = null;

    protected static ?string $badgeColor = null;

    protected static string | Htmlable | null $badgeTooltip = null;

    protected static bool $isBadgeDeferred = false;

    public function mount(): void
    {
        $this->loadDefaultActiveTab();
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    public static function make(array $properties = []): RelationManagerConfiguration
    {
        return app(RelationManagerConfiguration::class, ['relationManager' => static::class, 'properties' => $properties]);
    }

    /**
     * @return array<string>
     */
    public function getRenderHookScopes(): array
    {
        return [
            static::class,
            $this->getPageClass(),
        ];
    }

    public function render(): View|ViewResult
    {
        return \Webkernel\View\View::resolve($this->view, $this->getViewData());
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [];
    }

    public static function getTabComponent(Model $ownerRecord, string $pageClass): Tab
    {
        $isTabBadgeDeferred = static::isBadgeDeferred($ownerRecord, $pageClass);

        return Tab::make(static::getTitle($ownerRecord, $pageClass))
            ->badge($isTabBadgeDeferred
                ? static fn (): ?string => static::getBadge($ownerRecord, $pageClass)
                : static::getBadge($ownerRecord, $pageClass))
            ->deferBadge($isTabBadgeDeferred)
            ->badgeColor(static::getBadgeColor($ownerRecord, $pageClass))
            ->badgeTooltip(static::getBadgeTooltip($ownerRecord, $pageClass))
            ->icon(static::getIcon($ownerRecord, $pageClass))
            ->iconPosition(static::getIconPosition($ownerRecord, $pageClass));
    }

    public static function getIcon(Model $ownerRecord, string $pageClass): string | BackedEnum | Htmlable | null
    {
        return static::$icon;
    }

    public static function getIconPosition(Model $ownerRecord, string $pageClass): IconPosition
    {
        return static::$iconPosition;
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return static::$badge;
    }

    public static function getBadgeColor(Model $ownerRecord, string $pageClass): ?string
    {
        return static::$badgeColor;
    }

    public static function getBadgeTooltip(Model $ownerRecord, string $pageClass): string | Htmlable | null
    {
        return static::$badgeTooltip;
    }

    public static function isBadgeDeferred(Model $ownerRecord, string $pageClass): bool
    {
        return static::$isBadgeDeferred;
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return static::$title ?? static::getRelationshipTitle();
    }

    /**
     * @return class-string<Page>
     */
    public function getPageClass(): string
    {
        return $this->pageClass;
    }

    public function getOwnerRecord(): Model
    {
        return $this->ownerRecord;
    }

    public function isReadOnly(): bool
    {
        if (blank($this->getPageClass())) {
            return false;
        }

        $panel = Platform::getCurrentOrDefaultPanel();

        if (! $panel) {
            return false;
        }

        if (! $panel->hasReadOnlyRelationManagersOnResourceViewPagesByDefault()) {
            return false;
        }

        return is_subclass_of($this->getPageClass(), ViewRecord::class);
    }

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    public static function getRecordTitleAttribute(): ?string
    {
        return static::$recordTitleAttribute;
    }

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    protected static function getRecordLabel(): ?string
    {
        return static::$label;
    }

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    protected static function getModelLabel(): ?string
    {
        return static::$modelLabel ?? static::getRecordLabel();
    }

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    protected static function getPluralRecordLabel(): ?string
    {
        return static::$pluralLabel;
    }

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    protected static function getPluralModelLabel(): ?string
    {
        return static::$pluralModelLabel ?? static::getPluralRecordLabel();
    }

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    public function getInverseRelationshipName(): ?string
    {
        return static::$inverseRelationship;
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        if (static::shouldSkipAuthorization()) {
            return true;
        }

        if ($relatedResource = static::getRelatedResource()) {
            return $relatedResource::canAccess();
        }

        $model = $ownerRecord->{static::getRelationshipName()}()->getQuery()->getModel()::class;

        try {
            return authorize('viewAny', $model, static::shouldCheckPolicyExistence())->allowed();
        } catch (AuthorizationException $exception) {
            return $exception->toResponse()->allowed();
        }
    }

    protected function makeTable(): Table
    {
        return $this->makeBaseRelationshipTable()
            ->when(static::getInverseRelationshipName(), fn (Table $table, ?string $inverseRelationshipName): Table => $table->inverseRelationship($inverseRelationshipName))
            ->when(static::getModelLabel(), fn (Table $table, string $modelLabel): Table => $table->modelLabel($modelLabel))
            ->when(static::getPluralModelLabel(), fn (Table $table, string $pluralModelLabel): Table => $table->pluralModelLabel($pluralModelLabel))
            ->when(static::getRecordTitleAttribute(), fn (Table $table, string $recordTitleAttribute): Table => $table->recordTitleAttribute($recordTitleAttribute))
            ->heading($this->getTableHeading() ?? static::getTitle($this->getOwnerRecord(), $this->getPageClass()));
    }

    /**
     * @return array<string, mixed>
     */
    public static function getDefaultProperties(): array
    {
        $properties = [];

        if (static::isLazy()) {
            $properties['lazy'] = true;
        }

        return $properties;
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getTabsContentComponent(),
                RenderHook::make(PanelsRenderHook::RESOURCE_RELATION_MANAGER_BEFORE),
                EmbeddedTable::make(),
                RenderHook::make(PanelsRenderHook::RESOURCE_RELATION_MANAGER_AFTER),
            ]);
    }

    protected function canReorder(): bool
    {
        return $this->isReadOnly() ? false : $this->baseCanReorder();
    }

    public function getDefaultActionAuthorizationResponse(Action $action): ?Response
    {
        // Security: `AssociateAction`, `AttachAction`, `DetachAction`, and
        // `DissociateAction` only check `isReadOnly()` — they do not check
        // specific policy methods. `DeleteBulkAction`, `ForceDeleteBulkAction`,
        // and `RestoreBulkAction` use `*Any()` policy methods for performance.
        // Use `authorizeIndividualRecords()` if per-record checks are needed.

        if ($action instanceof ViewAction) {
            return $this->getViewAuthorizationResponse($action->getRecord());
        }

        return match (true) {
            $action instanceof AssociateAction, $action instanceof AttachAction, $action instanceof DetachAction, $action instanceof DetachBulkAction, $action instanceof DissociateAction, $action instanceof DissociateBulkAction, $action instanceof ImportAction => $this->isReadOnly() ? Response::deny() : null,
            $action instanceof CreateAction => $this->isReadOnly() ? Response::deny() : $this->getCreateAuthorizationResponse(),
            $action instanceof DeleteAction => $this->isReadOnly() ? Response::deny() : $this->getDeleteAuthorizationResponse($action->getRecord()),
            $action instanceof DeleteBulkAction => $this->isReadOnly() ? Response::deny() : $this->getDeleteAnyAuthorizationResponse(),
            $action instanceof EditAction => $this->isReadOnly() ? Response::deny() : $this->getEditAuthorizationResponse($action->getRecord()),
            $action instanceof ForceDeleteAction => $this->isReadOnly() ? Response::deny() : $this->getForceDeleteAuthorizationResponse($action->getRecord()),
            $action instanceof ForceDeleteBulkAction => $this->isReadOnly() ? Response::deny() : $this->getForceDeleteAnyAuthorizationResponse(),
            $action instanceof ReplicateAction => $this->isReadOnly() ? Response::deny() : $this->getReplicateAuthorizationResponse($action->getRecord()),
            $action instanceof RestoreAction => $this->isReadOnly() ? Response::deny() : $this->getRestoreAuthorizationResponse($action->getRecord()),
            $action instanceof RestoreBulkAction => $this->isReadOnly() ? Response::deny() : $this->getRestoreAnyAuthorizationResponse(),
            default => null,
        };
    }

    public function getDefaultActionIndividualRecordAuthorizationResponseResolver(Action $action): ?Closure
    {
        return match (true) {
            $action instanceof DeleteBulkAction => fn (Model $record): Response => $this->getDeleteAuthorizationResponse($record),
            $action instanceof ForceDeleteBulkAction => fn (Model $record): Response => $this->getForceDeleteAuthorizationResponse($record),
            $action instanceof RestoreBulkAction => fn (Model $record): Response => $this->getRestoreAuthorizationResponse($record),
            default => null,
        };
    }

    public function getDefaultActionSchemaResolver(Action $action): ?Closure
    {
        return match (true) {
            $action instanceof CreateAction, $action instanceof EditAction => fn (Schema $schema): Schema => $this->form($this->defaultForm($schema)),
            $action instanceof ViewAction => fn (Schema $schema): Schema => $this->infolist($this->defaultInfolist($this->form($this->defaultForm($schema)))),
            default => null,
        };
    }

    public function getDefaultActionUrl(Action $action): ?string
    {
        $relatedResource = static::getRelatedResource();

        if (! $relatedResource) {
            return null;
        }

        $actionModel = $action->getModel();

        if (
            ($action instanceof CreateAction) &&
            ($relatedResource::hasPage('create')) &&
            (blank($actionModel) || ($actionModel === $relatedResource::getModel()))
        ) {
            return $relatedResource::getUrl('create', shouldGuessMissingParameters: true);
        }

        if (
            ($action instanceof EditAction) &&
            ($relatedResource::hasPage('edit')) &&
            (blank($actionModel) || ($actionModel === $relatedResource::getModel()))
        ) {
            return $relatedResource::getUrl('edit', ['record' => $action->getRecord()], shouldGuessMissingParameters: true);
        }

        if (
            ($action instanceof ViewAction) &&
            ($relatedResource::hasPage('view')) &&
            (blank($actionModel) || ($actionModel === $relatedResource::getModel()))
        ) {
            return $relatedResource::getUrl('view', ['record' => $action->getRecord()], shouldGuessMissingParameters: true);
        }

        return null;
    }
}
