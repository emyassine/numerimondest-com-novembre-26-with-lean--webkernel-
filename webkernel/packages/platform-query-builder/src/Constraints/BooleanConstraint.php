<?php

namespace Webkernel\Platform\QueryBuilder\Constraints;

use Webkernel\Platform\QueryBuilder\Constraints\BooleanConstraint\Operators\IsTrueOperator;
use Webkernel\Platform\QueryBuilder\Constraints\Operators\IsFilledOperator;
use Webkernel\Platform\QueryBuilder\View\QueryBuilderIconAlias;
use Webkernel\Platform\Support\Facades\PlatformIcon;
use Webkernel\Platform\Support\Icons\Heroicon;

class BooleanConstraint extends Constraint
{
    use Concerns\CanBeNullable;

    protected function setUp(): void
    {
        parent::setUp();

        $this->icon(PlatformIcon::resolve(QueryBuilderIconAlias::CONSTRAINTS_BOOLEAN) ?? Heroicon::CheckCircle);

        $this->operators([
            IsTrueOperator::class,
            IsFilledOperator::make()
                ->visible(fn (): bool => $this->isNullable()),
        ]);
    }
}
