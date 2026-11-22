<?php

namespace Webkernel\Platform\Actions;

use Webkernel\Platform\Actions\Concerns\CanCustomizeProcess;
use Webkernel\Platform\Actions\View\ActionsIconAlias;
use Webkernel\Platform\Support\Facades\PlatformIcon;
use Webkernel\Platform\Support\Icons\Heroicon;
use Webkernel\Platform\Tables\Table;
use Webkernel\ORM\Model;
use Webkernel\ORM\Relations\BelongsTo;

class DissociateAction extends Action
{
    use CanCustomizeProcess;

    public static function getDefaultName(): ?string
    {
        return 'dissociate';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('webkernel-actions::dissociate.single.label'));

        $this->modalHeading(fn (): string => __('webkernel-actions::dissociate.single.modal.heading', ['label' => $this->getRecordTitle()]));

        $this->modalSubmitActionLabel(__('webkernel-actions::dissociate.single.modal.actions.dissociate.label'));

        $this->successNotificationTitle(__('webkernel-actions::dissociate.single.notifications.dissociated.title'));

        $this->defaultColor('danger');

        $this->icon(PlatformIcon::resolve(ActionsIconAlias::DISSOCIATE_ACTION) ?? Heroicon::XMark);

        $this->requiresConfirmation();

        $this->modalIcon(PlatformIcon::resolve(ActionsIconAlias::DISSOCIATE_ACTION_MODAL) ?? Heroicon::OutlinedXMark);

        $this->action(function (): void {
            $result = $this->process(function (Model $record, Table $table): bool {
                /** @var BelongsTo $inverseRelationship */
                $inverseRelationship = $table->getInverseRelationshipFor($record);

                $inverseRelationship->dissociate();

                return $record->save();
            });

            if ($result === false) {
                $this->failure();

                return;
            }

            $this->success();
        });
    }
}
