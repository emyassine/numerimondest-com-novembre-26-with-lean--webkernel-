<?php

namespace Webkernel\ORM\Factories;

use Webkernel\ORM\Model;
use Webkernel\Utilities\Collection;

class BelongsToManyRelationship
{
    /**
     * The related factory instance.
     *
     * @var \Webkernel\ORM\Factories\Factory|\Webkernel\Utilities\Collection|\Webkernel\ORM\Model|array
     */
    protected $factory;

    /**
     * The pivot attributes / attribute resolver.
     *
     * @var callable|array
     */
    protected $pivot;

    /**
     * The relationship name.
     *
     * @var string
     */
    protected $relationship;

    /**
     * Create a new attached relationship definition.
     *
     * @param  \Webkernel\ORM\Factories\Factory|\Webkernel\Utilities\Collection|\Webkernel\ORM\Model|array  $factory
     * @param  callable|array  $pivot
     * @param  string  $relationship
     */
    public function __construct($factory, $pivot, $relationship)
    {
        $this->factory = $factory;
        $this->pivot = $pivot;
        $this->relationship = $relationship;
    }

    /**
     * Create the attached relationship for the given model.
     *
     * @param  \Webkernel\ORM\Model  $model
     * @return void
     */
    public function createFor(Model $model)
    {
        $factoryInstance = $this->factory instanceof Factory;

        if ($factoryInstance) {
            $relationship = $model->{$this->relationship}();
        }

        Collection::wrap($factoryInstance ? $this->factory->prependState($relationship->getQuery()->pendingAttributes)->create([], $model) : $this->factory)->each(function ($attachable) use ($model) {
            $model->{$this->relationship}()->attach(
                $attachable,
                is_callable($this->pivot) ? call_user_func($this->pivot, $model) : $this->pivot
            );
        });
    }

    /**
     * Specify the model instances to always use when creating relationships.
     *
     * @param  \Webkernel\Utilities\Collection  $recycle
     * @return static
     */
    public function recycle($recycle)
    {
        $relationship = clone $this;

        if ($this->factory instanceof Factory) {
            $relationship->factory = $this->factory->recycle($recycle);
        }

        return $relationship;
    }
}
