<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Queue;

use \Webkernel\ORM\Contracts\ModelIdentifier;
use \Webkernel\ORM\Contracts\QueueableCollection;
use \Webkernel\ORM\Contracts\QueueableEntity;
use \Webkernel\ORM\Collection as EloquentCollection;
use \Webkernel\ORM\Relations\Concerns\AsPivot;
use \Webkernel\ORM\Relations\Pivot;
use \Webkernel\Utilities\Collection;

trait SerializesAndRestoresModelIdentifiers
{
    /**
     * Get the property value prepared for serialization.
     *
     * @param  mixed  $value
     * @param  bool  $withRelations
     * @return mixed
     */
    protected function getSerializedPropertyValue($value, $withRelations = true)
    {
        if ($value instanceof QueueableCollection) {
            return (new ModelIdentifier(
                $value->getQueueableClass(),
                $value->getQueueableIds(),
                $withRelations ? $value->getQueueableRelations() : [],
                $value->getQueueableConnection()
            ))->useCollectionClass(
                ($collectionClass = get_class($value)) !== EloquentCollection::class
                    ? $collectionClass
                    : null
            );
        }

        if ($value instanceof QueueableEntity) {
            return new ModelIdentifier(
                get_class($value),
                $value->getQueueableId(),
                $withRelations ? $value->getQueueableRelations() : [],
                $value->getQueueableConnection()
            );
        }

        return $value;
    }

    /**
     * Get the restored property value after deserialization.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function getRestoredPropertyValue($value)
    {
        if ($value instanceof \__PHP_Incomplete_Class) {
            throw new IncompatibleJobPayloadException(
                'This queued job contains a model identifier from an incompatible Webkernel release. Drain or recreate persisted jobs before deploying this release.'
            );
        }

        if (! $value instanceof ModelIdentifier) {
            return $value;
        }

        return is_array($value->id)
            ? $this->restoreCollection($value)
            : $this->restoreModel($value);
    }

    /**
     * Restore a queueable collection instance.
     *
     * @param  \Webkernel\ORM\Contracts\ModelIdentifier  $value
     * @return \Webkernel\ORM\Collection
     */
    protected function restoreCollection($value)
    {
        $class = $value->getClass();

        if (! $class || count($value->id) === 0) {
            return ! is_null($value->collectionClass ?? null)
                ? new $value->collectionClass
                : new EloquentCollection;
        }

        $collection = $this->getQueryForModelRestoration(
            (new $class)->setConnection($value->connection), $value->id
        )->useWritePdo()->get();

        if (is_a($class, Pivot::class, true) ||
            in_array(AsPivot::class, class_uses($class))) {
            return $collection;
        }

        $collection = $collection->keyBy->getKey();

        $collectionClass = get_class($collection);

        return (new $collectionClass(
            (new Collection($value->id))
                ->map(fn ($id) => $collection[$id] ?? null)
                ->filter()
        ))->loadMissing($value->relations ?? []);
    }

    /**
     * Restore the model from the model identifier instance.
     *
     * @param  \Webkernel\ORM\Contracts\ModelIdentifier  $value
     * @return \Webkernel\ORM\Model
     */
    public function restoreModel($value)
    {
        return $this->getQueryForModelRestoration(
            (new ($value->getClass()))->setConnection($value->connection), $value->id
        )->useWritePdo()->firstOrFail()->loadMissing($value->relations ?? []);
    }

    /**
     * Get the query for model restoration.
     *
     * @template TModel of \Webkernel\ORM\Model
     *
     * @param  TModel  $model
     * @param  array|int  $ids
     * @return \Webkernel\ORM\Builder<TModel>
     */
    protected function getQueryForModelRestoration($model, $ids)
    {
        return $model->newQueryForRestoration($ids);
    }
}
