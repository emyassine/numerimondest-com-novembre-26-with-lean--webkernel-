<?php

namespace Webkernel\Platform\Actions;

use Webkernel\Platform\Support\Facades\PlatformIcon;
use Webkernel\Platform\Support\Icons\Heroicon;
use Webkernel\Platform\Tables\View\TablesIconAlias;

class BulkActionGroup extends ActionGroup
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('webkernel-tables::table.actions.open_bulk_actions.label'));

        $this->icon(PlatformIcon::resolve(TablesIconAlias::ACTIONS_OPEN_BULK_ACTIONS) ?? Heroicon::EllipsisVertical);

        $this->defaultColor('gray');

        $this->button();

        $this->dropdownPlacement('bottom-start');

        $this->labeledFrom('sm');
    }

    /**
     * @return array<mixed>
     */
    public function getExtraDropdownAttributes(): array
    {
        return [
            'x-cloak' => true,
            'x-show' => 'getSelectedRecordsCount()',
            ...parent::getExtraDropdownAttributes(),
        ];
    }
}
