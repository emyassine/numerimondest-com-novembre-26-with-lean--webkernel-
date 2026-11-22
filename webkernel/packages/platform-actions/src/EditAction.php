<?php

namespace Webkernel\Platform\Actions;

use Closure;
use Webkernel\Platform\Actions\Concerns\CanCustomizeProcess;
use Webkernel\Platform\Actions\Contracts\HasActions;
use Webkernel\Platform\Actions\View\ActionsIconAlias;
use Webkernel\Platform\Schemas\Contracts\HasSchemas;
use Webkernel\Platform\Support\Facades\PlatformIcon;
use Webkernel\Platform\Support\Icons\Heroicon;
use Webkernel\Platform\Tables\Table;
use Webkernel\ORM\Model;
use Webkernel\ORM\Relations\BelongsToMany;
use Webkernel\Utilities\Arr;

class EditAction extends Action
{
    use CanCustomizeProcess;

    protected ?Closure $mutateRecordDataUsing = null;

    public static function getDefaultName(): ?string
    {
        return 'edit';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('webkernel-actions::edit.single.label'));

        $this->modalHeading(fn (): string => __('webkernel-actions::edit.single.modal.heading', ['label' => $this->getRecordTitle()]));

        $this->modalSubmitActionLabel(__('webkernel-actions::edit.single.modal.actions.save.label'));

        $this->successNotificationTitle(__('webkernel-actions::edit.single.notifications.saved.title'));

        $this->defaultColor('primary');

        $this->tableIcon(PlatformIcon::resolve(ActionsIconAlias::EDIT_ACTION) ?? Heroicon::PencilSquare);
        $this->groupedIcon(PlatformIcon::resolve(ActionsIconAlias::EDIT_ACTION_GROUPED) ?? Heroicon::PencilSquare);

        $this->fillForm(function (HasActions & HasSchemas $livewire, Model $record, ?Table $table): array {
            $translatableContentDriver = $livewire->makePlatformTranslatableContentDriver();

            if ($translatableContentDriver) {
                $data = $translatableContentDriver->getRecordAttributesToArray($record);
            } else {
                $data = $record->attributesToArray();
            }

            $relationship = $table?->getRelationship();

            if ($relationship instanceof BelongsToMany) {
                $pivot = $record->getRelationValue($relationship->getPivotAccessor());

                $pivotColumns = $relationship->getPivotColumns();

                if ($translatableContentDriver) {
                    $data = [
                        ...$data,
                        ...Arr::only($translatableContentDriver->getRecordAttributesToArray($pivot), $pivotColumns),
                    ];
                } else {
                    $data = [
                        ...$data,
                        ...Arr::only($pivot->attributesToArray(), $pivotColumns),
                    ];
                }
            }

            if ($this->mutateRecordDataUsing) {
                $data = $this->evaluate($this->mutateRecordDataUsing, ['data' => $data]);
            }

            return $data;
        });

        $this->action(function (): void {
            $this->process(function (array $data, HasActions & HasSchemas $livewire, Model $record, ?Table $table): void {
                $relationship = $table?->getRelationship();

                $translatableContentDriver = $livewire->makePlatformTranslatableContentDriver();

                if ($relationship instanceof BelongsToMany) {
                    $pivot = $record->getRelationValue($relationship->getPivotAccessor());

                    $pivotColumns = $relationship->getPivotColumns();
                    $pivotData = Arr::only($data, $pivotColumns);

                    if (count($pivotColumns)) {
                        if ($translatableContentDriver) {
                            $translatableContentDriver->updateRecord($pivot, $pivotData);
                        } else {
                            $pivot->update($pivotData);
                        }
                    }

                    $data = Arr::except($data, $pivotColumns);
                }

                if ($translatableContentDriver) {
                    $translatableContentDriver->updateRecord($record, $data);
                } else {
                    $record->update($data);
                }
            });

            $this->success();
        });
    }

    public function mutateRecordDataUsing(?Closure $callback): static
    {
        $this->mutateRecordDataUsing = $callback;

        return $this;
    }
}
