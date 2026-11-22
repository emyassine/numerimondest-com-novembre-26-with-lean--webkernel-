<?php

namespace Webkernel\Platform\Actions;

use Webkernel\Platform\Actions\Concerns\CanCustomizeProcess;
use Webkernel\Platform\Actions\View\ActionsIconAlias;
use Webkernel\Platform\Support\Facades\PlatformIcon;
use Webkernel\Platform\Support\Icons\Heroicon;
use Webkernel\ORM\Model;

class RestoreAction extends Action
{
    use CanCustomizeProcess;

    public static function getDefaultName(): ?string
    {
        return 'restore';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('webkernel-actions::restore.single.label'));

        $this->modalHeading(fn (): string => __('webkernel-actions::restore.single.modal.heading', ['label' => $this->getRecordTitle()]));

        $this->modalSubmitActionLabel(__('webkernel-actions::restore.single.modal.actions.restore.label'));

        $this->successNotificationTitle(__('webkernel-actions::restore.single.notifications.restored.title'));

        $this->defaultColor('gray');

        $this->tableIcon(PlatformIcon::resolve(ActionsIconAlias::RESTORE_ACTION) ?? Heroicon::ArrowUturnLeft);
        $this->groupedIcon(PlatformIcon::resolve(ActionsIconAlias::RESTORE_ACTION_GROUPED) ?? Heroicon::ArrowUturnLeft);

        $this->requiresConfirmation();

        $this->modalIcon(PlatformIcon::resolve(ActionsIconAlias::RESTORE_ACTION_MODAL) ?? Heroicon::OutlinedArrowUturnLeft);

        $this->action(function (Model $record): void {
            if (! method_exists($record, 'restore')) {
                $this->failure();

                return;
            }

            $result = $this->process(static fn (): ?bool => $record->restore());

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
