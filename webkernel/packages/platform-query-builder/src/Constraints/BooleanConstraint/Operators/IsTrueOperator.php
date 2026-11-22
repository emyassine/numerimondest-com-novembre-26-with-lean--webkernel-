<?php

namespace Webkernel\Platform\QueryBuilder\Constraints\BooleanConstraint\Operators;

use Webkernel\Platform\QueryBuilder\Constraints\Operators\Operator;
use Webkernel\ORM\Builder;
use Webkernel\ORM\Model;

class IsTrueOperator extends Operator
{
    public function getName(): string
    {
        return 'isTrue';
    }

    public function getLabel(): string
    {
        return __(
            $this->isInverse() ?
                'webkernel-query-builder::query-builder.operators.boolean.is_true.label.inverse' :
                'webkernel-query-builder::query-builder.operators.boolean.is_true.label.direct',
        );
    }

    public function getSummary(): string
    {
        return __(
            $this->isInverse() ?
                'webkernel-query-builder::query-builder.operators.boolean.is_true.summary.inverse' :
                'webkernel-query-builder::query-builder.operators.boolean.is_true.summary.direct',
            ['attribute' => $this->getConstraint()->getAttributeLabel()],
        );
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function apply(Builder $query, string $qualifiedColumn): Builder
    {
        return $query->where($qualifiedColumn, ! $this->isInverse());
    }
}
