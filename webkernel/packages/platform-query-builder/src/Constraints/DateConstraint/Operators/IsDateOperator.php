<?php

namespace Webkernel\Platform\QueryBuilder\Constraints\DateConstraint\Operators;

use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Actions\ActionGroup;
use Webkernel\Platform\Forms\Components\DatePicker;
use Webkernel\Platform\QueryBuilder\Constraints\Operators\Operator;
use Webkernel\Platform\Schemas\Components\Component;
use Webkernel\ORM\Builder;
use Webkernel\Utilities\Carbon;

class IsDateOperator extends Operator
{
    public function getName(): string
    {
        return 'isDate';
    }

    public function getLabel(): string
    {
        return __(
            $this->isInverse() ?
                'webkernel-query-builder::query-builder.operators.date.is_date.label.inverse' :
                'webkernel-query-builder::query-builder.operators.date.is_date.label.direct',
        );
    }

    public function getSummary(): string
    {
        return __(
            $this->isInverse() ?
                'webkernel-query-builder::query-builder.operators.date.is_date.summary.inverse' :
                'webkernel-query-builder::query-builder.operators.date.is_date.summary.direct',
            [
                'attribute' => $this->getConstraint()->getAttributeLabel(),
                'date' => ($date = $this->getDateSetting('date')) === null ? null : Carbon::parse($date)->toFormattedDateString(),
            ],
        );
    }

    /**
     * @return array<Component | Action | ActionGroup>
     */
    public function getFormSchema(): array
    {
        return [
            DatePicker::make('date')
                ->label(__('webkernel-query-builder::query-builder.operators.date.form.date.label'))
                ->required(),
        ];
    }

    public function apply(Builder $query, string $qualifiedColumn): Builder
    {
        $date = $this->getDateSetting('date');

        // Security: skip applying the constraint when the tampered setting is not a scalar date.
        if ($date === null) {
            return $query;
        }

        return $query->whereDate($qualifiedColumn, $this->isInverse() ? '!=' : '=', $date);
    }
}
