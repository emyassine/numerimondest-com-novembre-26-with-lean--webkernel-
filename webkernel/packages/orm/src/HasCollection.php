<?php

namespace Webkernel\ORM;

use Webkernel\ORM\Attributes\CollectedBy;
use ReflectionClass;

/**
 * @template TCollection of \Webkernel\ORM\Collection
 */
trait HasCollection
{
    /**
     * The resolved collection class names by model.
     *
     * @var array<class-string<static>, class-string<TCollection>>
     */
    protected static array $resolvedCollectionClasses = [];

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array<array-key, \Webkernel\ORM\Model>  $models
     * @return TCollection
     */
    public function newCollection(array $models = [])
    {
        static::$resolvedCollectionClasses[static::class] ??= ($this->resolveCollectionFromAttribute() ?? static::$collectionClass);

        $collection = new static::$resolvedCollectionClasses[static::class]($models);

        if (Model::isAutomaticallyEagerLoadingRelationships()) {
            $collection->withRelationshipAutoloading();
        }

        return $collection;
    }

    /**
     * Resolve the collection class name from the CollectedBy attribute.
     *
     * @return class-string<TCollection>|null
     */
    public function resolveCollectionFromAttribute()
    {
        $reflection = new ReflectionClass(static::class);

        do {
            $attributes = $reflection->getAttributes(CollectedBy::class);

            if (isset($attributes[0], $attributes[0]->getArguments()[0])) {
                return $attributes[0]->getArguments()[0];
            }
        } while ($reflection = $reflection->getParentClass());

        return null;
    }
}
