<?php

namespace Webkernel\Platform\Tables\Contracts;

use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Schemas\Schema;
use Webkernel\Platform\Support\Contracts\TranslatableContentDriver;
use Webkernel\Platform\Tables\Filters\Indicator;
use Webkernel\Platform\Tables\Grouping\Group;
use Webkernel\Platform\Tables\Table;
use \Webkernel\Database\Contracts\Pagination\CursorPaginator;
use \Webkernel\Database\Contracts\Pagination\Paginator;
use Webkernel\ORM\Builder;
use Webkernel\ORM\Collection as EloquentCollection;
use Webkernel\ORM\Model;
use Webkernel\Utilities\Collection;
use Webkernel\Utilities\LazyCollection;

interface HasTable
{
    public function callTableColumnAction(string $name, string $recordKey): mixed;

    public function deselectAllTableRecords(): void;

    public function getActiveTableLocale(): ?string;

    /**
     * @return array<int | string>
     */
    public function getAllSelectableTableRecordKeys(): array;

    public function getAllTableRecordsCount(): int;

    public function getAllSelectableTableRecordsCount(): int;

    /**
     * @return array<string, mixed> | null
     */
    public function getTableFilterState(string $name): ?array;

    /**
     * @return array<string, mixed> | null
     */
    public function getTableFilterFormState(string $name): ?array;

    public function getSelectedTableRecords(bool $shouldFetchSelectedRecords = true, ?int $chunkSize = null): EloquentCollection | Collection | LazyCollection;

    public function getSelectedTableRecordsQuery(bool $shouldFetchSelectedRecords = true, ?int $chunkSize = null): Builder;

    public function parseTableFilterName(string $name): string;

    public function getTableGrouping(): ?Group;

    public function getMountedTableAction(): ?Action;

    public function getMountedTableActionForm(): ?Schema;

    public function getMountedTableActionRecord(): ?Model;

    public function getMountedTableBulkAction(): ?Action;

    public function getMountedTableBulkActionForm(): ?Schema;

    public function getTable(): Table;

    public function getTableFiltersForm(): Schema;

    public function getTableRecords(): Collection | Paginator | CursorPaginator;

    public function getTableRecordsPerPage(): int | string | null;

    public function getTablePage(): int | string;

    public function getTableSortColumn(): ?string;

    public function getTableSortDirection(): ?string;

    public function getAllTableSummaryQuery(): ?Builder;

    public function getPageTableSummaryQuery(): ?Builder;

    public function isTableColumnToggledHidden(string $name): bool;

    /**
     * @return Model | array<string, mixed> | null
     */
    public function getTableRecord(?string $key): Model | array | null;

    /**
     * @param  Model | array<string, mixed>  $record
     */
    public function getTableRecordKey(Model | array $record): string;

    public function toggleTableReordering(): void;

    public function isTableReordering(): bool;

    public function isTableLoaded(): bool;

    public function hasTableSearch(): bool;

    public function resetTableSearch(): void;

    public function resetTableColumnSearch(string $column): void;

    public function getTableSearchIndicator(): Indicator;

    /**
     * @return array<Indicator>
     */
    public function getTableColumnSearchIndicators(): array;

    public function getFilteredTableQuery(): ?Builder;

    public function getFilteredSortedTableQuery(): ?Builder;

    public function getTableQueryForExport(): Builder;

    public function makePlatformTranslatableContentDriver(): ?TranslatableContentDriver;

    /**
     * @param  array<string, mixed>  $arguments
     */
    public function callMountedTableAction(array $arguments = []): mixed;

    /**
     * @param  array<string, mixed>  $arguments
     */
    public function mountTableAction(string $name, ?string $record = null, array $arguments = []): mixed;

    /**
     * @param  array<string, mixed>  $arguments
     */
    public function replaceMountedTableAction(string $name, ?string $record = null, array $arguments = []): void;

    /**
     * @param  array<int | string> | null  $selectedRecords
     */
    public function mountTableBulkAction(string $name, ?array $selectedRecords = null): mixed;

    /**
     * @param  array<int | string> | null  $selectedRecords
     */
    public function replaceMountedTableBulkAction(string $name, ?array $selectedRecords = null): void;
}
