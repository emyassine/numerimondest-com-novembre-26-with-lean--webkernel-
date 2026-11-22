<?php

namespace Webkernel\ORM;

use BadMethodCallException;
use Webkernel\ORM\Relations\HasMany;
use Webkernel\ORM\Relations\MorphMany;
use Webkernel\ORM\Relations\MorphOneOrMany;
use Webkernel\Utilities\Str;
use Webkernel\Utilities\Stringable;

/**
 * @template TIntermediateModel of \Webkernel\ORM\Model
 * @template TDeclaringModel of \Webkernel\ORM\Model
 * @template TLocalRelationship of \Webkernel\ORM\Relations\HasOneOrMany<TIntermediateModel, TDeclaringModel>
 */
class PendingHasThroughRelationship
{
    /**
     * The root model that the relationship exists on.
     *
     * @var TDeclaringModel
     */
    protected $rootModel;

    /**
     * The local relationship.
     *
     * @var TLocalRelationship
     */
    protected $localRelationship;

    /**
     * Create a pending has-many-through or has-one-through relationship.
     *
     * @param  TDeclaringModel  $rootModel
     * @param  TLocalRelationship  $localRelationship
     */
    public function __construct($rootModel, $localRelationship)
    {
        $this->rootModel = $rootModel;

        $this->localRelationship = $localRelationship;
    }

    /**
     * Define the distant relationship that this model has.
     *
     * @template TRelatedModel of \Webkernel\ORM\Model
     *
     * @param  string|(callable(TIntermediateModel): (\Webkernel\ORM\Relations\HasOne<TRelatedModel, TIntermediateModel>|\Webkernel\ORM\Relations\HasMany<TRelatedModel, TIntermediateModel>|\Webkernel\ORM\Relations\MorphMany<TRelatedModel, TIntermediateModel>|\Webkernel\ORM\Relations\MorphOneOrMany<TRelatedModel, TIntermediateModel>))  $callback
     * @return (
     *     $callback is string
     *     ? \Webkernel\ORM\Relations\HasManyThrough<\Webkernel\ORM\Model, TIntermediateModel, TDeclaringModel>|\Webkernel\ORM\Relations\HasOneThrough<\Webkernel\ORM\Model, TIntermediateModel, TDeclaringModel>
     *     : (
     *         TLocalRelationship is \Webkernel\ORM\Relations\HasMany<TIntermediateModel, TDeclaringModel>
     *         ? \Webkernel\ORM\Relations\HasManyThrough<TRelatedModel, TIntermediateModel, TDeclaringModel>
     *         : (
     *              $callback is callable(TIntermediateModel): (\Webkernel\ORM\Relations\HasMany<TRelatedModel, TIntermediateModel>|\Webkernel\ORM\Relations\MorphMany<TRelatedModel, TIntermediateModel>)
     *              ? \Webkernel\ORM\Relations\HasManyThrough<TRelatedModel, TIntermediateModel, TDeclaringModel>
     *              : \Webkernel\ORM\Relations\HasOneThrough<TRelatedModel, TIntermediateModel, TDeclaringModel>
     *         )
     *     )
     * )
     */
    public function has($callback)
    {
        if (is_string($callback)) {
            $callback = fn () => $this->localRelationship->getRelated()->{$callback}();
        }

        $distantRelation = $callback($this->localRelationship->getRelated());

        if ($distantRelation instanceof HasMany || $distantRelation instanceof MorphMany ||
            $this->localRelationship instanceof HasMany || $this->localRelationship instanceof MorphMany) {
            $returnedRelation = $this->rootModel->hasManyThrough(
                $distantRelation->getRelatedClass(),
                $this->localRelationship->getRelatedClass(),
                $this->localRelationship->getForeignKeyName(),
                $distantRelation->getForeignKeyName(),
                $this->localRelationship->getLocalKeyName(),
                $distantRelation->getLocalKeyName(),
            );
        } else {
            $returnedRelation = $this->rootModel->hasOneThrough(
                $distantRelation->getRelatedClass(),
                $this->localRelationship->getRelatedClass(),
                $this->localRelationship->getForeignKeyName(),
                $distantRelation->getForeignKeyName(),
                $this->localRelationship->getLocalKeyName(),
                $distantRelation->getLocalKeyName(),
            );
        }

        if ($this->localRelationship instanceof MorphOneOrMany) {
            $returnedRelation->where($this->localRelationship->getQualifiedMorphType(), $this->localRelationship->getMorphClass());
        }

        return $returnedRelation;
    }

    /**
     * Handle dynamic method calls into the model.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        if (Str::startsWith($method, 'has')) {
            return $this->has((new Stringable($method))->after('has')->lcfirst()->toString());
        }

        throw new BadMethodCallException(sprintf(
            'Call to undefined method %s::%s()', static::class, $method
        ));
    }
}
