<?php

namespace Webkernel\Platform\Actions;

use Webkernel\Platform\Actions\Concerns\CanCustomizeProcess;
use Webkernel\Platform\Actions\View\ActionsIconAlias;
use Webkernel\Platform\Support\Facades\PlatformIcon;
use Webkernel\Platform\Support\Icons\Heroicon;
use Webkernel\Platform\Tables\Contracts\HasTable;
use Webkernel\Platform\Tables\Filters\TrashedFilter;
use Webkernel\ORM\Collection as EloquentCollection;
use Webkernel\ORM\Model;
use Webkernel\Utilities\Collection;
use Webkernel\Utilities\LazyCollection;
use Webkernel\Utilities\Number;
use Throwable;

class RestoreBulkAction extends BulkAction
{
    use CanCustomizeProcess;

    public static function getDefaultName(): ?string
    {
        return 'restore';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('webkernel-actions::restore.multiple.label'));

        $this->modalHeading(fn (): string => __('webkernel-actions::restore.multiple.modal.heading', ['label' => $this->getTitleCasePluralModelLabel()]));

        $this->modalSubmitActionLabel(__('webkernel-actions::restore.multiple.modal.actions.restore.label'));

        $this->successNotificationTitle(__('webkernel-actions::restore.multiple.notifications.restored.title'));

        $this->failureNotificationTitle(function (int $successCount, int $totalCount): string {
            if ($successCount) {
                return trans_choice('webkernel-actions::restore.multiple.notifications.restored_partial.title', $successCount, [
                    'count' => Number::format($successCount),
                    'total' => Number::format($totalCount),
                ]);
            }

            return trans_choice('webkernel-actions::restore.multiple.notifications.restored_none.title', $totalCount, [
                'count' => Number::format($totalCount),
                'total' => Number::format($totalCount),
            ]);
        });

        $this->missingBulkAuthorizationFailureNotificationMessage(function (int $failureCount, int $totalCount): string {
            return trans_choice(
                ($failureCount === $totalCount)
                    ? 'webkernel-actions::restore.multiple.notifications.restored_none.missing_authorization_failure_message'
                    : 'webkernel-actions::restore.multiple.notifications.restored_partial.missing_authorization_failure_message',
                $failureCount,
                ['count' => Number::format($failureCount)],
            );
        });

        $this->missingBulkProcessingFailureNotificationMessage(function (int $failureCount, int $totalCount): string {
            return trans_choice(
                ($failureCount === $totalCount)
                    ? 'webkernel-actions::restore.multiple.notifications.restored_none.missing_processing_failure_message'
                    : 'webkernel-actions::restore.multiple.notifications.restored_partial.missing_processing_failure_message',
                $failureCount,
                ['count' => Number::format($failureCount)],
            );
        });

        $this->defaultColor('gray');

        $this->icon(PlatformIcon::resolve(ActionsIconAlias::RESTORE_ACTION) ?? Heroicon::ArrowUturnLeft);

        $this->requiresConfirmation();

        $this->modalIcon(PlatformIcon::resolve(ActionsIconAlias::RESTORE_ACTION_MODAL) ?? Heroicon::OutlinedArrowUturnLeft);

        $this->action(function (): void {
            $this->process(static function (RestoreBulkAction $action, EloquentCollection | Collection | LazyCollection $records): void {
                if (! $action->shouldFetchSelectedRecords()) {
                    try {
                        $action->reportBulkProcessingSuccessfulRecordsCount(
                            $action->getSelectedRecordsQuery()->restore(), /** @phpstan-ignore method.notFound */
                        );
                    } catch (Throwable $exception) {
                        $action->reportCompleteBulkProcessingFailure();

                        report($exception);
                    }

                    return;
                }

                $isFirstException = true;

                $records->each(static function (Model $record) use ($action, &$isFirstException): void {
                    if (! method_exists($record, 'restore')) {
                        return;
                    }

                    try {
                        $record->restore() || $action->reportBulkProcessingFailure();
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

        $this->hidden(function (HasTable $livewire): bool {
            $trashedFilterState = $livewire->getTableFilterState(TrashedFilter::class) ?? [];

            if (! array_key_exists('value', $trashedFilterState)) {
                return false;
            }

            return blank($trashedFilterState['value']);
        });
    }
}
