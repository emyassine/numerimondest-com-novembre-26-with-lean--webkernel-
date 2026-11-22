<?php

namespace Webkernel\Platform\QueryBuilder\Constraints\NumberConstraint\Operators\Concerns;

use Webkernel\Platform\Forms\Components\Select;
use Webkernel\Platform\QueryBuilder\Constraints\NumberConstraint;
use Webkernel\Database\Connection;
use Webkernel\ORM\Builder;
use Webkernel\ORM\Relations\BelongsToMany;
use Webkernel\ORM\Relations\HasOneOrMany;
use Webkernel\ORM\Relations\Relation;
use LogicException;

trait CanAggregateRelationships
{
    public static function getAggregateSelectName(): string
    {
        return 'aggregate';
    }

    public static function getAggregateAverageKey(): string
    {
        return 'avg';
    }

    public static function getAggregateMaxKey(): string
    {
        return 'max';
    }

    public static function getAggregateMinKey(): string
    {
        return 'min';
    }

    public static function getAggregateSumKey(): string
    {
        return 'sum';
    }

    public function queriesRelationshipsUsingSubSelect(): bool
    {
        return parent::queriesRelationshipsUsingSubSelect() && blank($this->getSettings()[static::getAggregateSelectName()]);
    }

    protected function getNumericCastType(Builder $query): string
    {
        /** @var Connection $databaseConnection */
        $databaseConnection = $query->getConnection();

        $driver = $databaseConnection->getDriverName();

        return match ($driver) {
            'sqlite' => 'real',
            'pgsql' => 'numeric',
            default => 'decimal(10,2)',
        };
    }

    protected function applyAggregateComparison(Builder $query, string $operator, float $value): Builder
    {
        $relationshipName = $this->getConstraint()->getRelationshipName();
        $attributeForQuery = $this->getConstraint()->getAttributeForQuery();
        $aggregate = $this->getAggregate();
        $modifyRelationshipQueryUsing = $this->getConstraint()->getModifyRelationshipQueryUsing();

        /** @var Relation $relationship */
        $relationship = Relation::noConstraints(
            static fn (): Relation => $query->getModel()->{$relationshipName}(),
        );

        $relatedModel = $relationship->getModel();
        $castType = $this->getNumericCastType($query);

        if (! ($relationship instanceof BelongsToMany) && ! ($relationship instanceof HasOneOrMany)) {
            throw new LogicException('Relationship type [' . get_class($relationship) . '] is not supported for aggregate queries.');
        }

        $subQuery = $relationship->getRelationExistenceQuery(
            $relatedModel->newQueryWithoutRelationships(),
            $query,
            [],
        )->mergeConstraintsFrom($relationship->getQuery());

        $attributeForQuery = $subQuery->qualifyColumn($attributeForQuery);
        $attributeForQuery = $subQuery->getQuery()->getGrammar()->wrap($attributeForQuery);

        $subQuery->selectRaw("cast({$aggregate}({$attributeForQuery}) as {$castType})");

        if ($modifyRelationshipQueryUsing) {
            $subQuery = $this->evaluate($modifyRelationshipQueryUsing, ['query' => $subQuery]) ?? $subQuery;
        }

        return $query->whereRaw("({$subQuery->toSql()}) {$operator} ?", [...$subQuery->getBindings(), $value]);
    }

    protected function getAggregateSelect(): Select
    {
        return Select::make(static::getAggregateSelectName())
            ->label(__('webkernel-query-builder::query-builder.operators.number.form.aggregate.label'))
            ->options([
                static::getAggregateSumKey() => __('webkernel-query-builder::query-builder.operators.number.aggregates.sum.label'),
                static::getAggregateAverageKey() => __('webkernel-query-builder::query-builder.operators.number.aggregates.average.label'),
                static::getAggregateMaxKey() => __('webkernel-query-builder::query-builder.operators.number.aggregates.max.label'),
                static::getAggregateMinKey() => __('webkernel-query-builder::query-builder.operators.number.aggregates.min.label'),
            ])
            ->visible($this->getConstraint()->queriesRelationships());
    }

    protected function getAggregate(): ?string
    {
        $aggregate = $this->getSettings()[static::getAggregateSelectName()] ?? null;

        if ($aggregate === null) {
            return null;
        }

        // Security: a tampered request can set the aggregate to a non-scalar (e.g. an array),
        // which would throw a `TypeError` at the `array_key_exists()` offset check below. Fail
        // closed by treating anything that is not a recognised option as no aggregate.
        if (! is_scalar($aggregate)) {
            return null;
        }

        if (! array_key_exists($aggregate, $this->getAggregateSelect()->getOptions())) {
            return null;
        }

        return (string) $aggregate;
    }

    protected function getAttributeLabel(): string
    {
        $attributeLabel = $this->getConstraint()->getAttributeLabel();

        return __(match ($this->getAggregate()) {
            static::getAggregateAverageKey() => 'webkernel-query-builder::query-builder.operators.number.aggregates.average.summary',
            static::getAggregateMaxKey() => 'webkernel-query-builder::query-builder.operators.number.aggregates.max.summary',
            static::getAggregateMinKey() => 'webkernel-query-builder::query-builder.operators.number.aggregates.min.summary',
            static::getAggregateSumKey() => 'webkernel-query-builder::query-builder.operators.number.aggregates.sum.summary',
            default => $attributeLabel,
        }, ['attribute' => $attributeLabel]);
    }

    public function getConstraint(): ?NumberConstraint
    {
        $constraint = parent::getConstraint();

        if (! ($constraint instanceof NumberConstraint)) {
            throw new LogicException('Constraint must be an instance of [' . NumberConstraint::class . '].');
        }

        return $constraint;
    }
}
