<?php

namespace Webkernel\Platform\Resources\Concerns;

use Webkernel\Platform\Schemas\Components\Component;
use Webkernel\Platform\Schemas\Components\Tabs;
use Webkernel\Platform\Schemas\Components\Tabs\Tab;
use Webkernel\ORM\Builder;

trait HasTabs
{
    public ?string $activeTab = null;

    /**
     * @var array<string | int, Tab>
     */
    protected array $cachedTabs;

    protected function loadDefaultActiveTab(): void
    {
        if (filled($this->activeTab)) {
            return;
        }

        $this->activeTab = $this->getDefaultActiveTab();
    }

    /**
     * @return array<string | int, Tab>
     */
    public function getTabs(): array
    {
        return [];
    }

    /**
     * @return array<string | int, Tab>
     */
    public function getCachedTabs(): array
    {
        return $this->cachedTabs ??= collect($this->getTabs())
            ->map(fn (Tab $tab, string | int $key): Tab => $tab->hasCustomLabel() ? $tab : $tab->label($this->generateTabLabel($key)))
            ->all();
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return array_key_first($this->getCachedTabs());
    }

    public function updatedActiveTab(): void
    {
        $this->resetPage();

        $this->cachedDefaultTableColumnState = null;

        $this->applyTableColumnManager();
    }

    public function generateTabLabel(string $key): string
    {
        return (string) str($key)
            ->replace(['_', '-'], ' ')
            ->ucfirst();
    }

    protected function modifyQueryWithActiveTab(Builder $query, bool $isResolvingRecord = false): Builder
    {
        if (blank($this->activeTab)) {
            return $query;
        }

        $tabs = $this->getCachedTabs();

        if (! array_key_exists($this->activeTab, $tabs)) {
            return $query;
        }

        $tab = $tabs[$this->activeTab];

        if ($isResolvingRecord && $tab->shouldExcludeQueryWhenResolvingRecord()) {
            return $query;
        }

        return $tab->modifyQuery($query);
    }

    public function getTabsContentComponent(): Component
    {
        $tabs = $this->getCachedTabs();

        return Tabs::make(__('webkernel-platform::resources/pages/list-records.tabs.label'))
            ->key('resourceTabs')
            ->livewireProperty('activeTab')
            ->tabPanels(false)
            ->contained(false)
            ->tabs($tabs)
            ->hidden(empty($tabs));
    }
}
