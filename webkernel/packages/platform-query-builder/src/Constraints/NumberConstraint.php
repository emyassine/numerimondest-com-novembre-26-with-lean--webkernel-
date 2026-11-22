<?php

namespace Webkernel\Platform\QueryBuilder\Constraints;

use Closure;
use Webkernel\Platform\QueryBuilder\Constraints\NumberConstraint\Operators\EqualsOperator;
use Webkernel\Platform\QueryBuilder\Constraints\NumberConstraint\Operators\IsMaxOperator;
use Webkernel\Platform\QueryBuilder\Constraints\NumberConstraint\Operators\IsMinOperator;
use Webkernel\Platform\QueryBuilder\Constraints\Operators\IsFilledOperator;
use Webkernel\Platform\QueryBuilder\View\QueryBuilderIconAlias;
use Webkernel\Platform\Support\Facades\PlatformIcon;
use Webkernel\Platform\Support\Icons\Heroicon;

class NumberConstraint extends Constraint
{
    use Concerns\CanBeNullable;

    /**
     * @var array<string, string>
     */
    protected array $existingAggregateAliases = [];

    protected bool | Closure $isInteger = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->icon(PlatformIcon::resolve(QueryBuilderIconAlias::CONSTRAINTS_NUMBER) ?? Heroicon::Variable);

        $this->operators([
            IsMinOperator::class,
            IsMaxOperator::class,
            EqualsOperator::class,
            IsFilledOperator::make()
                ->visible(fn (): bool => $this->isNullable()),
        ]);
    }

    public function integer(bool | Closure $condition = true): static
    {
        $this->isInteger = $condition;

        return $this;
    }

    public function isInteger(): bool
    {
        return (bool) $this->evaluate($this->isInteger);
    }

    public function reportAggregateAlias(string $alias): static
    {
        $this->existingAggregateAliases[$alias] = $alias;

        return $this;
    }

    public function isExistingAggregateAlias(string $alias): bool
    {
        return array_key_exists($alias, $this->existingAggregateAliases);
    }
}
