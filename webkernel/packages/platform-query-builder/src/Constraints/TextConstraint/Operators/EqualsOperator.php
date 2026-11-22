<?php

namespace Webkernel\Platform\QueryBuilder\Constraints\TextConstraint\Operators;

use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Actions\ActionGroup;
use Webkernel\Platform\Forms\Components\TextInput;
use Webkernel\Platform\QueryBuilder\Constraints\Operators\Operator;
use Webkernel\Platform\Schemas\Components\Component;
use Webkernel\Database\Connection;
use Webkernel\ORM\Builder;
use Webkernel\Database\Query\Expression;
use Webkernel\Utilities\Str;

use function Webkernel\Platform\Support\apply_search_constraint;
use function Webkernel\Platform\Support\generate_search_pattern;

class EqualsOperator extends Operator
{
    public function getName(): string
    {
        return 'equals';
    }

    public function getLabel(): string
    {
        return __(
            $this->isInverse() ?
                'webkernel-query-builder::query-builder.operators.text.equals.label.inverse' :
                'webkernel-query-builder::query-builder.operators.text.equals.label.direct',
        );
    }

    public function getSummary(): string
    {
        return __(
            $this->isInverse() ?
                'webkernel-query-builder::query-builder.operators.text.equals.summary.inverse' :
                'webkernel-query-builder::query-builder.operators.text.equals.summary.direct',
            [
                'attribute' => $this->getConstraint()->getAttributeLabel(),
                'text' => $this->getStringSetting('text'),
            ],
        );
    }

    /**
     * @return array<Component | Action | ActionGroup>
     */
    public function getFormSchema(): array
    {
        return [
            TextInput::make('text')
                ->label(__('webkernel-query-builder::query-builder.operators.text.form.text.label'))
                ->required()
                ->columnSpanFull(),
        ];
    }

    public function apply(Builder $query, string $qualifiedColumn): Builder
    {
        // Security: skip applying the constraint when the tampered setting is not a scalar string.
        $text = $this->getStringSetting('text');

        if ($text === null) {
            return $query;
        }

        /** @var Connection $databaseConnection */
        $databaseConnection = $query->getConnection();

        $isPostgres = $databaseConnection->getDriverName() === 'pgsql';

        if ($isPostgres) {
            $parts = explode('.', $qualifiedColumn);

            if (count($parts) === 3) {
                [$schema, $table, $column] = $parts;
                $table = "{$schema}.{$table}";
            } else {
                [$table, $column] = $parts;
            }

            if (Str::lower($table) !== $table) {
                $table = collect(explode('.', $table))
                    ->map(fn (string $segment): string => "\"{$segment}\"")
                    ->implode('.');
            }

            if (Str::lower($column) !== $column) {
                $column = "\"{$column}\"";
            }

            $qualifiedColumn = new Expression("lower({$table}.{$column}::text)");
            $text = Str::lower($text);
        }

        return apply_search_constraint(
            $query,
            $qualifiedColumn,
            generate_search_pattern($text, hasLeadingWildcard: false, hasTrailingWildcard: false),
            isInverse: $this->isInverse(),
        );
    }
}
