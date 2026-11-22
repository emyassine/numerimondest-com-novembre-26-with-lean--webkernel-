<?php

namespace Webkernel\Platform\Actions;

use Webkernel\Platform\Actions\Concerns\CanCustomizeProcess;
use Webkernel\Platform\Actions\View\ActionsIconAlias;
use Webkernel\Platform\Support\Facades\PlatformIcon;
use Webkernel\Platform\Support\Icons\Heroicon;
use Webkernel\ORM\Model;

class DeleteAction extends Action
{
    use CanCustomizeProcess;

    public static function getDefaultName(): ?string
    {
        return 'delete';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('webkernel-actions::delete.single.label'));

        $this->modalHeading(fn (): string => __('webkernel-actions::delete.single.modal.heading', ['label' => $this->getRecordTitle()]));

        $this->modalSubmitActionLabel(__('webkernel-actions::delete.single.modal.actions.delete.label'));

        $this->successNotificationTitle(__('webkernel-actions::delete.single.notifications.deleted.title'));

        $this->defaultColor('danger');

        $this->tableIcon(PlatformIcon::resolve(ActionsIconAlias::DELETE_ACTION) ?? Heroicon::Trash);
        $this->groupedIcon(PlatformIcon::resolve(ActionsIconAlias::DELETE_ACTION_GROUPED) ?? Heroicon::Trash);

        $this->requiresConfirmation();

        $this->modalIcon(PlatformIcon::resolve(ActionsIconAlias::DELETE_ACTION_MODAL) ?? Heroicon::OutlinedTrash);

        $this->keyBindings(['mod+d']);

        $this->hidden(static function (Model $record): bool {
            if (! method_exists($record, 'trashed')) {
                return false;
            }

            return $record->trashed();
        });

        $this->action(function (): void {
            $result = $this->process(static fn (Model $record): ?bool => $record->delete());

            if (! $result) {
                $this->failure();

                return;
            }

            $this->success();
        });
    }
}
