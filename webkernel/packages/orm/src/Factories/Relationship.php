<?php

namespace Webkernel\ORM\Factories;

use Webkernel\ORM\Model;
use Webkernel\ORM\Relations\BelongsToMany;
use Webkernel\ORM\Relations\HasOneOrMany;
use Webkernel\ORM\Relations\MorphOneOrMany;

class Relationship
{
    /**
     * The related factory instance.
     *
     * @var \Webkernel\ORM\Factories\Factory
     */
    protected $factory;

    /**
     * The relationship name.
     *
     * @var string
     */
    protected $relationship;

    /**
     * Create a new child relationship instance.
     *
     * @param  \Webkernel\ORM\Factories\Factory  $factory
     * @param  string  $relationship
     */
    public function __construct(Factory $factory, $relationship)
    {
        $this->factory = $factory;
        $this->relationship = $relationship;
    }

    /**
     * Create the child relationship for the given parent model.
     *
     * @param  \Webkernel\ORM\Model  $parent
     * @return void
     */
    public function createFor(Model $parent)
    {
        $relationship = $parent->{$this->relationship}();

        if ($relationship instanceof MorphOneOrMany) {
            $this->factory->state([
                $relationship->getMorphType() => $relationship->getMorphClass(),
                $relationship->getForeignKeyName() => $relationship->getParentKey(),
            ])->prependState($relationship->getQuery()->pendingAttributes)->create([], $parent);
        } elseif ($relationship instanceof HasOneOrMany) {
            $this->factory->state([
                $relationship->getForeignKeyName() => $relationship->getParentKey(),
            ])->prependState($relationship->getQuery()->pendingAttributes)->create([], $parent);
        } elseif ($relationship instanceof BelongsToMany) {
            $relationship->attach(
                $this->factory->prependState($relationship->getQuery()->pendingAttributes)->create([], $parent)
            );
        }
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

        $relationship->factory = $this->factory->recycle($recycle);

        return $relationship;
    }
}
