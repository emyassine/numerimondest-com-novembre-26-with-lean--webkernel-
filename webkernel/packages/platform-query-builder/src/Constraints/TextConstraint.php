<?php

namespace Webkernel\Platform\QueryBuilder\Constraints;

use Webkernel\Platform\QueryBuilder\Constraints\Operators\IsFilledOperator;
use Webkernel\Platform\QueryBuilder\Constraints\TextConstraint\Operators\ContainsOperator;
use Webkernel\Platform\QueryBuilder\Constraints\TextConstraint\Operators\EndsWithOperator;
use Webkernel\Platform\QueryBuilder\Constraints\TextConstraint\Operators\EqualsOperator;
use Webkernel\Platform\QueryBuilder\Constraints\TextConstraint\Operators\StartsWithOperator;
use Webkernel\Platform\QueryBuilder\View\QueryBuilderIconAlias;
use Webkernel\Platform\Support\Facades\PlatformIcon;
use Webkernel\Platform\Support\Icons\Heroicon;

class TextConstraint extends Constraint
{
    use Concerns\CanBeNullable;

    protected function setUp(): void
    {
        parent::setUp();

        $this->icon(PlatformIcon::resolve(QueryBuilderIconAlias::CONSTRAINTS_TEXT) ?? Heroicon::Language);

        $this->operators([
            ContainsOperator::class,
            StartsWithOperator::class,
            EndsWithOperator::class,
            EqualsOperator::class,
            IsFilledOperator::make()
                ->visible(fn (): bool => $this->isNullable()),
        ]);
    }
}
