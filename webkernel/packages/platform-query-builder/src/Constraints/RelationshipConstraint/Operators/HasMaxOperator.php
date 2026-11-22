<?php

namespace Webkernel\Platform\QueryBuilder\Constraints\RelationshipConstraint\Operators;

use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Actions\ActionGroup;
use Webkernel\Platform\Forms\Components\TextInput;
use Webkernel\Platform\QueryBuilder\Constraints\Operators\Operator;
use Webkernel\Platform\Schemas\Components\Component;
use Webkernel\ORM\Builder;

class HasMaxOperator extends Operator
{
    public function getName(): string
    {
        return 'hasMax';
    }

    public function getLabel(): string
    {
        return __(
            $this->isInverse() ?
                'webkernel-query-builder::query-builder.operators.relationship.has_max.label.inverse' :
                'webkernel-query-builder::query-builder.operators.relationship.has_max.label.direct',
        );
    }

    public function getSummary(): string
    {
        return __(
            $this->isInverse() ?
                'webkernel-query-builder::query-builder.operators.relationship.has_max.summary.inverse' :
                'webkernel-query-builder::query-builder.operators.relationship.has_max.summary.direct',
            [
                'relationship' => $this->getConstraint()->getAttributeLabel(),
                'count' => ($count = $this->getNumericSetting('count')) === null ? null : (int) $count,
            ],
        );
    }

    /**
     * @return array<Component | Action | ActionGroup>
     */
    public function getFormSchema(): array
    {
        return [
            TextInput::make('count')
                ->label(__('webkernel-query-builder::query-builder.operators.relationship.form.count.label'))
                ->numeric()
                ->required()
                ->minValue(1),
        ];
    }

    public function applyToBaseQuery(Builder $query): Builder
    {
        $count = $this->getNumericSetting('count');

        // Security: skip applying the constraint when the tampered setting is not numeric.
        if ($count === null) {
            return $query;
        }

        $modifyRelationshipQueryUsing = $this->getConstraint()->getModifyRelationshipQueryUsing();

        return $query->has(
            $this->getConstraint()->getRelationshipName(),
            $this->isInverse() ? '>' : '<=',
            (int) $count,
            'and',
            function (Builder $query) use ($modifyRelationshipQueryUsing): Builder {
                if ($modifyRelationshipQueryUsing) {
                    $query = $this->evaluate($modifyRelationshipQueryUsing, [
                        'query' => $query,
                    ]) ?? $query;
                }

                return $query;
            },
        );
    }
}
