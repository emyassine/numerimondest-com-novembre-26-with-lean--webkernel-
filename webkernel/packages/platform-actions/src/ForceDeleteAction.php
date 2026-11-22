<?php

namespace Webkernel\Platform\Actions;

use Webkernel\Platform\Actions\Concerns\CanCustomizeProcess;
use Webkernel\Platform\Actions\View\ActionsIconAlias;
use Webkernel\Platform\Support\Facades\PlatformIcon;
use Webkernel\Platform\Support\Icons\Heroicon;
use Webkernel\ORM\Model;

class ForceDeleteAction extends Action
{
    use CanCustomizeProcess;

    public static function getDefaultName(): ?string
    {
        return 'forceDelete';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('webkernel-actions::force-delete.single.label'));

        $this->modalHeading(fn (): string => __('webkernel-actions::force-delete.single.modal.heading', ['label' => $this->getRecordTitle()]));

        $this->modalSubmitActionLabel(__('webkernel-actions::force-delete.single.modal.actions.delete.label'));

        $this->successNotificationTitle(__('webkernel-actions::force-delete.single.notifications.deleted.title'));

        $this->defaultColor('danger');

        $this->tableIcon(PlatformIcon::resolve(ActionsIconAlias::FORCE_DELETE_ACTION) ?? Heroicon::Trash);
        $this->groupedIcon(PlatformIcon::resolve(ActionsIconAlias::FORCE_DELETE_ACTION_GROUPED) ?? Heroicon::Trash);

        $this->requiresConfirmation();

        $this->modalIcon(PlatformIcon::resolve(ActionsIconAlias::FORCE_DELETE_ACTION_MODAL) ?? Heroicon::OutlinedTrash);

        $this->action(function (): void {
            $result = $this->process(static fn (Model $record): ?bool => $record->forceDelete());

            if (! $result) {
                $this->failure();

                return;
            }

            $this->success();
        });

        $this->visible(static function (Model $record): bool {
            if (! method_exists($record, 'trashed')) {
                return false;
            }

            return $record->trashed();
        });
    }
}
