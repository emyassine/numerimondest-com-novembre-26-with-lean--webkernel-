<?php

namespace Webkernel\Platform\QueryBuilder\Constraints\DateConstraint;

use Webkernel\Platform\Support\Contracts\HasLabel;

enum DateUnit: string implements HasLabel
{
    case Second = 'second';
    case Minute = 'minute';
    case Hour = 'hour';
    case Day = 'day';
    case Week = 'week';
    case Month = 'month';
    case Quarter = 'quarter';
    case Year = 'year';

    public function getLabel(): string
    {
        return match ($this) {
            self::Second => __('webkernel-query-builder::query-builder.operators.date.unit_labels.second'),
            self::Minute => __('webkernel-query-builder::query-builder.operators.date.unit_labels.minute'),
            self::Hour => __('webkernel-query-builder::query-builder.operators.date.unit_labels.hour'),
            self::Day => __('webkernel-query-builder::query-builder.operators.date.unit_labels.day'),
            self::Week => __('webkernel-query-builder::query-builder.operators.date.unit_labels.week'),
            self::Month => __('webkernel-query-builder::query-builder.operators.date.unit_labels.month'),
            self::Quarter => __('webkernel-query-builder::query-builder.operators.date.unit_labels.quarter'),
            self::Year => __('webkernel-query-builder::query-builder.operators.date.unit_labels.year'),
        };
    }

    public function isTimeUnit(): bool
    {
        return in_array($this, [self::Second, self::Minute, self::Hour], strict: true);
    }
}
