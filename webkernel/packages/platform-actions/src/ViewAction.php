<?php

namespace Webkernel\Platform\Actions;

use Closure;
use Webkernel\Platform\Actions\Contracts\HasActions;
use Webkernel\Platform\Actions\View\ActionsIconAlias;
use Webkernel\Platform\Schemas\Contracts\HasSchemas;
use Webkernel\Platform\Support\Facades\PlatformIcon;
use Webkernel\Platform\Support\Icons\Heroicon;
use Webkernel\ORM\Model;

class ViewAction extends Action
{
    protected ?Closure $mutateRecordDataUsing = null;

    public static function getDefaultName(): ?string
    {
        return 'view';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('webkernel-actions::view.single.label'));

        $this->modalHeading(fn (): string => __('webkernel-actions::view.single.modal.heading', ['label' => $this->getRecordTitle()]));

        $this->modalSubmitAction(false);
        $this->modalCancelAction(fn (Action $action) => $action->label(__('webkernel-actions::view.single.modal.actions.close.label')));

        $this->defaultColor('gray');

        $this->tableIcon(PlatformIcon::resolve(ActionsIconAlias::VIEW_ACTION) ?? Heroicon::Eye);
        $this->groupedIcon(PlatformIcon::resolve(ActionsIconAlias::VIEW_ACTION_GROUPED) ?? Heroicon::Eye);

        $this->disabledSchema();

        $this->fillForm(function (HasActions & HasSchemas $livewire, Model $record): array {
            if ($translatableContentDriver = $livewire->makePlatformTranslatableContentDriver()) {
                $data = $translatableContentDriver->getRecordAttributesToArray($record);
            } else {
                $data = $record->attributesToArray();
            }

            if ($this->mutateRecordDataUsing) {
                $data = $this->evaluate($this->mutateRecordDataUsing, ['data' => $data]);
            }

            return $data;
        });
    }

    public function mutateRecordDataUsing(?Closure $callback): static
    {
        $this->mutateRecordDataUsing = $callback;

        return $this;
    }
}
