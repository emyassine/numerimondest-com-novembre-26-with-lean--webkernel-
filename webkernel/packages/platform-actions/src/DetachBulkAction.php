<?php

namespace Webkernel\Platform\Actions;

use Webkernel\Platform\Actions\Concerns\CanCustomizeProcess;
use Webkernel\Platform\Actions\View\ActionsIconAlias;
use Webkernel\Platform\Support\Facades\PlatformIcon;
use Webkernel\Platform\Support\Icons\Heroicon;
use Webkernel\Platform\Tables\Table;
use Webkernel\ORM\Collection as EloquentCollection;
use Webkernel\ORM\Model;
use Webkernel\ORM\Relations\BelongsToMany;
use Webkernel\Utilities\Collection;
use Webkernel\Utilities\LazyCollection;
use Throwable;

class DetachBulkAction extends BulkAction
{
    use CanCustomizeProcess;

    public static function getDefaultName(): ?string
    {
        return 'detach';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('webkernel-actions::detach.multiple.label'));

        $this->modalHeading(fn (): string => __('webkernel-actions::detach.multiple.modal.heading', ['label' => $this->getTitleCasePluralModelLabel()]));

        $this->modalSubmitActionLabel(__('webkernel-actions::detach.multiple.modal.actions.detach.label'));

        $this->successNotificationTitle(__('webkernel-actions::detach.multiple.notifications.detached.title'));

        $this->defaultColor('danger');

        $this->icon(PlatformIcon::resolve(ActionsIconAlias::DETACH_ACTION) ?? Heroicon::XMark);

        $this->requiresConfirmation();

        $this->modalIcon(PlatformIcon::resolve(ActionsIconAlias::DETACH_ACTION_MODAL) ?? Heroicon::OutlinedXMark);

        $this->action(function (): void {
            $this->process(function (DetachBulkAction $action, EloquentCollection | Collection | LazyCollection $records, Table $table): void {
                /** @var BelongsToMany $relationship */
                $relationship = $table->getRelationship();

                if (! $action->shouldFetchSelectedRecords()) {
                    try {
                        $action->reportBulkProcessingSuccessfulRecordsCount(
                            $relationship->detach($records),
                        );
                    } catch (Throwable $exception) {
                        $action->reportCompleteBulkProcessingFailure();

                        report($exception);
                    }

                    return;
                }

                $relationshipPivotAccessor = $relationship->getPivotAccessor();

                $isFirstException = true;

                $records->each(
                    function (Model $record) use ($action, &$isFirstException, $relationshipPivotAccessor): void {
                        try {
                            $record->getRelationValue($relationshipPivotAccessor)->delete() || $action->reportBulkProcessingFailure();
                        } catch (Throwable $exception) {
                            $action->reportBulkProcessingFailure();

                            if ($isFirstException) {
                                // Only report the first exception so as to not flood error logs. Even
                                // if Platform did not catch exceptions like this, only the first
                                // would be reported as the rest of the process would be halted.
                                report($exception);

                                $isFirstException = false;
                            }
                        }
                    },
                );
            });
        });

        $this->deselectRecordsAfterCompletion();
    }
}
