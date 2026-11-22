<?php

namespace Webkernel\Platform\Actions;

use Webkernel\Platform\Actions\Concerns\CanCustomizeProcess;
use Webkernel\Platform\Actions\View\ActionsIconAlias;
use Webkernel\Platform\Support\Facades\PlatformIcon;
use Webkernel\Platform\Support\Icons\Heroicon;
use Webkernel\Platform\Tables\Table;
use Webkernel\ORM\Model;
use Webkernel\ORM\Relations\BelongsToMany;

class DetachAction extends Action
{
    use CanCustomizeProcess;

    public static function getDefaultName(): ?string
    {
        return 'detach';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('webkernel-actions::detach.single.label'));

        $this->modalHeading(fn (): string => __('webkernel-actions::detach.single.modal.heading', ['label' => $this->getRecordTitle()]));

        $this->modalSubmitActionLabel(__('webkernel-actions::detach.single.modal.actions.detach.label'));

        $this->successNotificationTitle(__('webkernel-actions::detach.single.notifications.detached.title'));

        $this->defaultColor('danger');

        $this->icon(PlatformIcon::resolve(ActionsIconAlias::DETACH_ACTION) ?? Heroicon::XMark);

        $this->requiresConfirmation();

        $this->modalIcon(PlatformIcon::resolve(ActionsIconAlias::DETACH_ACTION_MODAL) ?? Heroicon::OutlinedXMark);

        $this->action(function (): void {
            $result = $this->process(function (Model $record, Table $table): bool {
                /** @var BelongsToMany $relationship */
                $relationship = $table->getRelationship();

                if ($table->allowsDuplicates()) {
                    return (bool) $record->getRelationValue($relationship->getPivotAccessor())->delete();
                }

                return (bool) $relationship->detach($record);
            });

            if ($result === false) {
                $this->failure();

                return;
            }

            $this->success();
        });
    }
}
