<?php

namespace Webkernel\Platform\QueryBuilder\Constraints;

use Closure;
use Webkernel\Platform\QueryBuilder\Constraints\DateConstraint\Operators\IsAfterOperator;
use Webkernel\Platform\QueryBuilder\Constraints\DateConstraint\Operators\IsBeforeOperator;
use Webkernel\Platform\QueryBuilder\Constraints\DateConstraint\Operators\IsDateOperator;
use Webkernel\Platform\QueryBuilder\Constraints\DateConstraint\Operators\IsMonthOperator;
use Webkernel\Platform\QueryBuilder\Constraints\DateConstraint\Operators\IsYearOperator;
use Webkernel\Platform\QueryBuilder\Constraints\Operators\IsFilledOperator;
use Webkernel\Platform\QueryBuilder\View\QueryBuilderIconAlias;
use Webkernel\Platform\Support\Facades\PlatformIcon;
use Webkernel\Platform\Support\Icons\Heroicon;

class DateConstraint extends Constraint
{
    use Concerns\CanBeNullable;

    protected bool | Closure $hasTime = false;

    public function time(bool | Closure $condition = true): static
    {
        $this->hasTime = $condition;

        return $this;
    }

    public function hasTime(): bool
    {
        return (bool) $this->evaluate($this->hasTime);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->icon(PlatformIcon::resolve(QueryBuilderIconAlias::CONSTRAINTS_DATE) ?? Heroicon::Calendar);

        $this->operators([
            IsAfterOperator::class,
            IsBeforeOperator::class,
            IsDateOperator::class,
            IsMonthOperator::class,
            IsYearOperator::class,
            IsFilledOperator::make()
                ->visible(fn (): bool => $this->isNullable()),
        ]);
    }
}
