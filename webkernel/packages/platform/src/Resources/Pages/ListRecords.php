<?php

namespace Webkernel\Platform\Resources\Pages;

use Closure;
use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Actions\CreateAction;
use Webkernel\Platform\Actions\EditAction;
use Webkernel\Platform\Actions\ViewAction;
use Webkernel\Platform\Facades\Platform;
use Webkernel\Platform\Navigation\NavigationGroup;
use Webkernel\Platform\Navigation\NavigationItem;
use Webkernel\Platform\Resources\Concerns\HasTabs;
use Webkernel\Platform\Schemas\Components\EmbeddedTable;
use Webkernel\Platform\Schemas\Components\RenderHook;
use Webkernel\Platform\Schemas\Schema;
use Webkernel\Platform\Tables;
use Webkernel\Platform\Tables\Table;
use Webkernel\Platform\View\PanelsRenderHook;
use Webkernel\View\Htmlable;
use Webkernel\ORM\Builder;
use Webkernel\ORM\Model;
use Webkernel\ORM\Relations\Relation;
use Webkernel\Liveview\Attributes\Url;

class ListRecords extends Page implements Tables\Contracts\HasTable
{
    use HasTabs;
    use Tables\Concerns\InteractsWithTable {
        makeTable as makeBaseTable;
    }

    #[Url(as: 'reordering')]
    public bool $isTableReordering = false;

    /**
     * @var array<string, mixed> | null
     */
    #[Url(as: 'filters')]
    public ?array $tableFilters = null;

    #[Url(as: 'grouping')]
    public ?string $tableGrouping = null;

    /**
     * @var ?string
     */
    #[Url(as: 'search')]
    public $tableSearch = '';

    #[Url(as: 'sort')]
    public ?string $tableSort = null;

    #[Url(as: 'tab')]
    public ?string $activeTab = null;

    public function mount(): void
    {
        $this->authorizeAccess();

        $this->loadDefaultActiveTab();
    }

    protected function authorizeAccess(): void {}

    public function getBreadcrumb(): ?string
    {
        return static::$breadcrumb ?? __('webkernel-platform::resources/pages/list-records.breadcrumb');
    }

    public function table(Table $table): Table
    {
        return $table;
    }

    public function getTitle(): string | Htmlable
    {
        return static::$title ?? static::getResource()::getTitleCasePluralModelLabel();
    }

    public function form(Schema $schema): Schema
    {
        return static::getResource()::form($schema);
    }

    public function infolist(Schema $schema): Schema
    {
        return static::getResource()::infolist($schema);
    }

    public function getDefaultActionSchemaResolver(Action $action): ?Closure
    {
        return match (true) {
            $action instanceof CreateAction, $action instanceof EditAction => fn (Schema $schema): Schema => $this->form($schema->hasCustomColumns() ? $schema : $schema->columns(2)),
            $action instanceof ViewAction => fn (Schema $schema): Schema => $this->infolist($this->form($schema->hasCustomColumns() ? $schema : $schema->columns(2))),
            default => null,
        };
    }

    /**
     * @return Model|class-string<Model>|null
     */
    protected function getMountedActionSchemaModel(): Model | string | null
    {
        return $this->getModel();
    }

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    public function getPluralModelLabel(): ?string
    {
        return null;
    }

    protected function makeTable(): Table
    {
        $table = $this->makeBaseTable()
            ->query(fn (): Builder => $this->getTableQuery())
            ->when(
                $this->getParentRecord(),
                fn (Table $table, Model $parentRecord): Table => $table->modifyQueryUsing(
                    fn (Builder $query) => static::getResource()::scopeEloquentQueryToParent($query, $parentRecord),
                ),
            )
            ->modifyQueryUsing($this->modifyQueryWithActiveTab(...))
            ->when($this->getModelLabel(), fn (Table $table, string $modelLabel): Table => $table->modelLabel($modelLabel))
            ->when($this->getPluralModelLabel(), fn (Table $table, string $pluralModelLabel): Table => $table->pluralModelLabel($pluralModelLabel))
            ->recordAction(function (Model $record, Table $table): ?string {
                foreach (['view', 'edit'] as $action) {
                    $action = $table->getAction($action);

                    if (! $action) {
                        continue;
                    }

                    $action->record($record);

                    $actionGroup = $action->getGroup();

                    while ($actionGroup) {
                        $actionGroup->record($record);

                        $actionGroup = $actionGroup->getGroup();
                    }

                    if ($action->isHidden()) {
                        continue;
                    }

                    if ($action->getUrl()) {
                        continue;
                    }

                    return $action->getName();
                }

                return null;
            });

        if (! $table->hasCustomRecordUrl()) {
            $table->recordUrl(function (Model $record, Table $table): ?string {
                foreach (['view', 'edit'] as $action) {
                    $action = $table->getAction($action);

                    if (! $action) {
                        continue;
                    }

                    $action = clone $action;

                    $action->record($record);

                    $actionGroup = $action->getGroup();

                    while ($actionGroup) {
                        $actionGroup->record($record);

                        $actionGroup = $actionGroup->getGroup();
                    }

                    if ($action->isHidden()) {
                        continue;
                    }

                    $url = $action->getUrl();

                    if (! $url) {
                        continue;
                    }

                    return $url;
                }

                $resource = static::getResource();

                foreach (['view', 'edit'] as $action) {
                    if (! $resource::hasPage($action)) {
                        continue;
                    }

                    if (! $resource::{'can' . ucfirst($action)}($record)) {
                        continue;
                    }

                    return $this->getResourceUrl($action, ['record' => $record]);
                }

                return null;
            });
        }

        static::getResource()::configureTable($table);

        return $table;
    }

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    protected function getTableQuery(): Builder | Relation | null
    {
        return static::getResource()::getEloquentQuery();
    }

    /**
     * @return array<NavigationItem | NavigationGroup>
     */
    public function getSubNavigation(): array
    {
        if (filled($cluster = static::getCluster()) && $cluster::shouldRegisterSubNavigation()) {
            return $this->generateNavigationItems($cluster::getClusteredComponents());
        }

        return [];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getTabsContentComponent(),
                RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE),
                EmbeddedTable::make(),
                RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER),
            ]);
    }

    /**
     * @return array<string>
     */
    public function getPageClasses(): array
    {
        return [
            'fi-resource-list-records-page',
            'fi-resource-' . str_replace('/', '-', $this->getResource()::getSlug(Platform::getCurrentOrDefaultPanel())),
        ];
    }
}
