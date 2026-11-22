<?php

namespace Webkernel\Platform\Pages\Dashboard\Actions;

use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Pages\Dashboard;
use Webkernel\Platform\Support\Facades\PlatformIcon;
use Webkernel\Platform\Support\Icons\Heroicon;
use Webkernel\Platform\View\PanelsIconAlias;
use Webkernel\Liveview\Component;
use LogicException;

class FilterAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'filter';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('webkernel-platform::pages/dashboard.actions.filter.label'));

        $this->modalHeading(fn (): string => __('webkernel-platform::pages/dashboard.actions.filter.modal.heading'));

        $this->modalSubmitActionLabel(__('webkernel-platform::pages/dashboard.actions.filter.modal.actions.apply.label'));

        $this->icon(PlatformIcon::resolve(PanelsIconAlias::PAGES_DASHBOARD_ACTIONS_FILTER) ?? Heroicon::Funnel);

        $this->defaultColor('gray');

        $this->fillForm(function (Component $livewire): ?array {
            if (! property_exists($livewire, 'filters')) {
                throw new LogicException('The [' . $liveview::class . '] page must implement the [' . Dashboard\Concerns\HasFilters::class . '] trait.');
            }

            return $livewire->filters;
        });

        $this->action(function (array $data, Component $livewire): void {
            if (! property_exists($livewire, 'filters')) {
                return;
            }

            $livewire->filters = $data;

            $this->success();
        });

        $this->slideOver();

        $this->modalWidth('md');
    }
}
