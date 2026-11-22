<?php

namespace Webkernel\Platform\Widgets\Concerns;

use Webkernel\Platform\Resources\Pages\Page as ResourcePage;
use Webkernel\Platform\Tables\Contracts\HasTable;
use \Webkernel\Database\Contracts\Pagination\Paginator;
use Webkernel\ORM\Builder;
use Webkernel\ORM\Collection;
use Webkernel\ORM\Model;
use Webkernel\Liveview\Attributes\Locked;
use Webkernel\Liveview\Attributes\Reactive;
use LogicException;

use function Webkernel\Liveview\trigger;

trait InteractsWithPageTable /** @phpstan-ignore trait.unused */
{
    /** @var array<string, int> */
    #[Reactive]
    public $paginators = [];

    #[Reactive]
    public ?int $tableRecordsCount = null;

    /**
     * @var array<string, string | array<string, string | null> | null>
     */
    #[Reactive]
    public array $tableColumnSearches = [];

    #[Reactive]
    public ?string $tableGrouping = null;

    /**
     * @var array<string, mixed> | null
     */
    #[Reactive]
    public ?array $tableFilters = null;

    #[Reactive]
    public int | string | null $tableRecordsPerPage = null;

    /**
     * @var ?string
     */
    #[Reactive]
    public $tableSearch = '';

    #[Reactive]
    public ?string $tableSort = null;

    #[Reactive]
    public ?string $activeTab = null;

    #[Reactive] #[Locked]
    public ?Model $parentRecord = null;

    protected HasTable $tablePage;

    protected function getTablePage(): string
    {
        throw new LogicException('You must define a `getTablePage()` method on your widget that returns the name of a Livewire component.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function getTablePageMountParameters(): array
    {
        return [];
    }

    protected function getTablePageInstance(): HasTable
    {
        if (isset($this->tablePage)) {
            return $this->tablePage;
        }

        /** @var HasTable $page */
        $page = app('liveview')->new($this->getTablePage());

        $properties = [
            'activeTab' => $this->activeTab,
            'paginators' => $this->paginators,
            'parentRecord' => $this->parentRecord,
            'tableColumnSearches' => $this->tableColumnSearches,
            'tableFilters' => $this->tableFilters,
            'tableGrouping' => $this->tableGrouping,
            'tableRecordsPerPage' => $this->tableRecordsPerPage,
            'tableSearch' => $this->tableSearch,
            'tableSort' => $this->tableSort,
            ...$this->getTablePageMountParameters(),
        ];

        if ($page instanceof ResourcePage) {
            $page->parentRecord = $properties['parentRecord'];
            $page->mountParentRecord();
            $properties['parentRecord'] = $page->getParentRecord();
        }

        trigger('mount', $page, [], null, null, []);

        foreach ($properties as $property => $value) {
            $page->{$property} = $value;
        }

        $page->bootedInteractsWithTable();

        return $this->tablePage = $page;
    }

    protected function getPageTableQuery(): Builder
    {
        return $this->getTablePageInstance()->getFilteredSortedTableQuery();
    }

    protected function getPageTableRecords(): Collection | Paginator
    {
        return $this->getTablePageInstance()->getTableRecords();
    }
}
