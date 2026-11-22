<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\ORM\Concerns;

use InvalidArgumentException;
use Webkernel\Database\Query\JoinClause;
use Webkernel\ORM\Relations\BelongsTo;
use Webkernel\ORM\Relations\BelongsToMany;
use Webkernel\ORM\Relations\HasOneOrMany;
use Webkernel\ORM\Relations\HasOneOrManyThrough;
use Webkernel\ORM\Relations\MorphOneOrMany;
use Webkernel\ORM\Relations\Relation;

trait JoinsRelationships
{
    /**
     * Join a model relationship to the query.
     *
     * @param string $relationship Relationship method on the queried model.
     * @return $this
     *
     * @throws InvalidArgumentException When the relationship cannot be joined directly.
     */
    public function joinRelationship(string $relationship, string $type = 'inner'): static
    {
        return $this->joinRelationshipUsing($relationship, $type);
    }

    /**
     * Add a left join for a model relationship.
     *
     * @param string $relationship Relationship method on the queried model.
     * @return $this
     */
    public function leftJoinRelationship(string $relationship): static
    {
        return $this->joinRelationshipUsing($relationship, 'left');
    }

    /**
     * Add the join represented by a relationship object.
     *
     * @param string $name Relationship method on the queried model.
     * @param string $type SQL join type.
     * @return $this
     */
    protected function joinRelationshipUsing(string $name, string $type): static
    {
        if (str_contains($name, '.')) {
            throw new InvalidArgumentException('Nested relationship joins are not supported.');
        }

        if (! in_array($type, ['inner', 'left', 'right'], true)) {
            throw new InvalidArgumentException("Unsupported relationship join type [{$type}].");
        }

        $relation = Relation::noConstraints(fn (): Relation => $this->getModel()->newInstance()->{$name}());
        $related_table = $relation->getRelated()->getTable();

        if ($relation instanceof BelongsTo) {
            $this->getQuery()->join(
                $related_table,
                $relation->getQualifiedForeignKeyName(),
                '=',
                $relation->getQualifiedOwnerKeyName(),
                $type,
            );

            return $this;
        }

        if ($relation instanceof BelongsToMany) {
            $this->getQuery()->join(
                $relation->getTable(),
                $relation->getQualifiedParentKeyName(),
                '=',
                $relation->getQualifiedForeignPivotKeyName(),
                $type,
            );
            $this->getQuery()->join(
                $related_table,
                $relation->getQualifiedRelatedPivotKeyName(),
                '=',
                $relation->getQualifiedRelatedKeyName(),
                $type,
            );

            return $this;
        }

        if ($relation instanceof HasOneOrManyThrough) {
            $this->getQuery()->join(
                $relation->getParent()->getTable(),
                $relation->getQualifiedLocalKeyName(),
                '=',
                $relation->getQualifiedFirstKeyName(),
                $type,
            );
            $this->getQuery()->join(
                $related_table,
                $relation->getQualifiedParentKeyName(),
                '=',
                $relation->getQualifiedFarKeyName(),
                $type,
            );

            return $this;
        }

        if ($relation instanceof HasOneOrMany) {
            if ($relation instanceof MorphOneOrMany) {
                $this->getQuery()->join(
                    $related_table,
                    function (JoinClause $join) use ($relation): void {
                        $join->on(
                            $relation->getQualifiedParentKeyName(),
                            '=',
                            $relation->getQualifiedForeignKeyName(),
                        )->where(
                            $relation->getQualifiedMorphType(),
                            '=',
                            $relation->getMorphClass(),
                        );
                    },
                    type: $type,
                );
            } else {
                $this->getQuery()->join(
                    $related_table,
                    $relation->getQualifiedParentKeyName(),
                    '=',
                    $relation->getQualifiedForeignKeyName(),
                    $type,
                );
            }

            return $this;
        }

        throw new InvalidArgumentException('This relationship type cannot be joined directly.');
    }
}
