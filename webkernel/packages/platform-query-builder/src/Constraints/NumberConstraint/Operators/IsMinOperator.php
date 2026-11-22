<?php

namespace Webkernel\Platform\QueryBuilder\Constraints\NumberConstraint\Operators;

use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Actions\ActionGroup;
use Webkernel\Platform\Forms\Components\TextInput;
use Webkernel\Platform\QueryBuilder\Constraints\Operators\Operator;
use Webkernel\Platform\Schemas\Components\Component;
use Webkernel\ORM\Builder;
use Webkernel\Utilities\Number;

class IsMinOperator extends Operator
{
    use Concerns\CanAggregateRelationships;

    public function getName(): string
    {
        return 'isMin';
    }

    public function getLabel(): string
    {
        return __(
            $this->isInverse() ?
                'webkernel-query-builder::query-builder.operators.number.is_min.label.inverse' :
                'webkernel-query-builder::query-builder.operators.number.is_min.label.direct',
        );
    }

    public function getSummary(): string
    {
        return __(
            $this->isInverse() ?
                'webkernel-query-builder::query-builder.operators.number.is_min.summary.inverse' :
                'webkernel-query-builder::query-builder.operators.number.is_min.summary.direct',
            [
                'attribute' => $this->getAttributeLabel(),
                'number' => ($number = $this->getNumericSetting('number')) === null ? null : Number::format($number),
            ],
        );
    }

    /**
     * @return array<Component | Action | ActionGroup>
     */
    public function getFormSchema(): array
    {
        return [
            TextInput::make('number')
                ->label(__('webkernel-query-builder::query-builder.operators.number.form.number.label'))
                ->numeric()
                ->integer($this->getConstraint()->isInteger())
                ->required(),
            $this->getAggregateSelect(),
        ];
    }

    public function apply(Builder $query, string $qualifiedColumn): Builder
    {
        $number = $this->getNumericSetting('number');

        // Security: skip applying the constraint when the tampered setting is not numeric.
        if ($number === null) {
            return $query;
        }

        if (filled($this->getAggregate())) {
            return $this->applyAggregateComparison($query, $this->isInverse() ? '<' : '>=', $number);
        }

        return $query->where($qualifiedColumn, $this->isInverse() ? '<' : '>=', $number);
    }
}
