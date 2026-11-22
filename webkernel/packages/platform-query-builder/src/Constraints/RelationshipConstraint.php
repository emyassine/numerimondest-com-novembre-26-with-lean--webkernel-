<?php

namespace Webkernel\Platform\QueryBuilder\Constraints;

use Closure;
use Webkernel\Platform\QueryBuilder\Constraints\RelationshipConstraint\Operators\EqualsOperator;
use Webkernel\Platform\QueryBuilder\Constraints\RelationshipConstraint\Operators\HasMaxOperator;
use Webkernel\Platform\QueryBuilder\Constraints\RelationshipConstraint\Operators\HasMinOperator;
use Webkernel\Platform\QueryBuilder\Constraints\RelationshipConstraint\Operators\IsEmptyOperator;
use Webkernel\Platform\QueryBuilder\Constraints\RelationshipConstraint\Operators\IsRelatedToOperator;
use Webkernel\Platform\QueryBuilder\View\QueryBuilderIconAlias;
use Webkernel\Platform\Support\Facades\PlatformIcon;
use Webkernel\Platform\Support\Icons\Heroicon;

class RelationshipConstraint extends Constraint
{
    protected bool | Closure $isMultiple = false;

    protected bool | Closure | null $canBeEmpty = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->icon(PlatformIcon::resolve(QueryBuilderIconAlias::CONSTRAINTS_RELATIONSHIP) ?? Heroicon::ArrowsPointingOut);

        $this->operators([
            HasMinOperator::make()
                ->visible(fn (): bool => $this->isMultiple()),
            HasMaxOperator::make()
                ->visible(fn (): bool => $this->isMultiple()),
            EqualsOperator::make()
                ->visible(fn (): bool => $this->isMultiple()),
            IsEmptyOperator::make()
                ->visible(fn (): bool => $this->canBeEmpty()),
        ]);
    }

    public function selectable(IsRelatedToOperator $operator): static
    {
        $this->unshiftOperators([$operator]);

        return $this;
    }

    public function multiple(bool | Closure $condition = true): static
    {
        $this->isMultiple = $condition;

        return $this;
    }

    public function isMultiple(): bool
    {
        return (bool) $this->evaluate($this->isMultiple);
    }

    public function emptyable(bool | Closure | null $condition = true): static
    {
        $this->canBeEmpty = $condition;

        return $this;
    }

    public function canBeEmpty(): bool
    {
        return $this->evaluate($this->canBeEmpty) ?? $this->isMultiple();
    }
}
