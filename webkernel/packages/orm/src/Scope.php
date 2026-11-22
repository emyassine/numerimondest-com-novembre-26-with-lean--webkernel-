<?php

namespace Webkernel\ORM;

/**
 * @template TModel of \Webkernel\ORM\Model
 */
interface Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Webkernel\ORM\Builder<covariant TModel>  $builder
     * @param  TModel  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model);
}
