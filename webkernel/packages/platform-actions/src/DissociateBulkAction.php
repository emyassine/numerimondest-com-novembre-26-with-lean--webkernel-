<?php

namespace Webkernel\Platform\Actions;

use Webkernel\Platform\Actions\Concerns\CanCustomizeProcess;
use Webkernel\Platform\Actions\View\ActionsIconAlias;
use Webkernel\Platform\Support\Facades\PlatformIcon;
use Webkernel\Platform\Support\Icons\Heroicon;
use Webkernel\Platform\Tables\Table;
use Webkernel\ORM\Collection as EloquentCollection;
use Webkernel\ORM\Model;
use Webkernel\ORM\Relations\BelongsTo;
use Webkernel\ORM\Relations\HasMany;
use Webkernel\Utilities\Collection;
use Webkernel\Utilities\LazyCollection;
use Throwable;

class DissociateBulkAction extends BulkAction
{
    use CanCustomizeProcess;

    public static function getDefaultName(): ?string
    {
        return 'dissociate';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('webkernel-actions::dissociate.multiple.label'));

        $this->modalHeading(fn (): string => __('webkernel-actions::dissociate.multiple.modal.heading', ['label' => $this->getTitleCasePluralModelLabel()]));

        $this->modalSubmitActionLabel(__('webkernel-actions::dissociate.multiple.modal.actions.dissociate.label'));

        $this->successNotificationTitle(__('webkernel-actions::dissociate.multiple.notifications.dissociated.title'));

        $this->defaultColor('danger');

        $this->icon(PlatformIcon::resolve(ActionsIconAlias::DISSOCIATE_ACTION) ?? Heroicon::XMark);

        $this->requiresConfirmation();

        $this->modalIcon(PlatformIcon::resolve(ActionsIconAlias::DISSOCIATE_ACTION_MODAL) ?? Heroicon::OutlinedXMark);

        $this->action(function (): void {
            $this->process(function (DissociateBulkAction $action, EloquentCollection | Collection | LazyCollection $records, Table $table): void {
                if (! $action->shouldFetchSelectedRecords()) {
                    /** @var HasMany $relationship */
                    $relationship = $table->getRelationship();

                    try {
                        $action->reportBulkProcessingSuccessfulRecordsCount(
                            $action->getSelectedRecordsQuery()->update([
                                $relationship->getQualifiedForeignKeyName() => null,
                            ]),
                        );
                    } catch (Throwable $exception) {
                        $action->reportCompleteBulkProcessingFailure();

                        report($exception);
                    }

                    return;
                }

                $isFirstException = true;

                $records->each(function (Model $record) use ($action, &$isFirstException, $table): void {
                    try {
                        /** @var BelongsTo $inverseRelationship */
                        $inverseRelationship = $table->getInverseRelationshipFor($record);

                        $inverseRelationship->dissociate();
                        $record->save() || $action->reportBulkProcessingFailure();
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
                });
            });
        });

        $this->deselectRecordsAfterCompletion();
    }
}
