<?php

namespace Webkernel\ORM\Relations;

use Webkernel\ORM\Collection as EloquentCollection;

/**
 * @template TRelatedModel of \Webkernel\ORM\Model
 * @template TDeclaringModel of \Webkernel\ORM\Model
 *
 * @extends \Webkernel\ORM\Relations\MorphOneOrMany<TRelatedModel, TDeclaringModel, \Webkernel\ORM\Collection<int, TRelatedModel>>
 */
class MorphMany extends MorphOneOrMany
{
    /**
     * Convert the relationship to a "morph one" relationship.
     *
     * @return \Webkernel\ORM\Relations\MorphOne<TRelatedModel, TDeclaringModel>
     */
    public function one()
    {
        return MorphOne::noConstraints(fn () => tap(
            new MorphOne(
                $this->getQuery(),
                $this->getParent(),
                $this->morphType,
                $this->foreignKey,
                $this->localKey
            ),
            function ($morphOne) {
                if ($inverse = $this->getInverseRelationship()) {
                    $morphOne->inverse($inverse);
                }
            }
        ));
    }

    /** @inheritDoc */
    public function getResults()
    {
        return ! is_null($this->getParentKey())
            ? $this->query->get()
            : $this->related->newCollection();
    }

    /** @inheritDoc */
    public function initRelation(array $models, $relation)
    {
        foreach ($models as $model) {
            $model->setRelation($relation, $this->related->newCollection());
        }

        return $models;
    }

    /** @inheritDoc */
    public function match(array $models, EloquentCollection $results, $relation)
    {
        return $this->matchMany($models, $results, $relation);
    }

    /** @inheritDoc */
    public function forceCreate(array $attributes = [])
    {
        $attributes[$this->getMorphType()] = $this->morphClass;

        return parent::forceCreate($attributes);
    }
}
