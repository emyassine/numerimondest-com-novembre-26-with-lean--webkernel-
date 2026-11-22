<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
// Adapted from AnourValar Eloquent Serialize and Laravel Serializable Closure; see the accompanying LICENSE-*.md files.
namespace Webkernel\ORM\Serialization\Grammars;

use Webkernel\ORM\Relations\HasOneOrMany;

trait ModelGrammar
{
    /**
     * Pack
     *
     * @param \Webkernel\ORM\Builder $builder
     * @return \Webkernel\ORM\Serialization\Package
     */
    protected function pack(\Webkernel\ORM\Builder $builder): \Webkernel\ORM\Serialization\Package
    {
        $this->setup();

        // Global scopes handle
        $builder = $builder->applyScopes();
        $builder->withoutGlobalScopes(array_keys($builder->getAllGlobalScopes()));

        return new \Webkernel\ORM\Serialization\Package([
            'model' => get_class($builder->getModel()),
            'connection' => $builder->getModel()->getConnectionName(),
            'eloquent' => $this->packEloquentBuilder($builder),
            'query' => $this->packQueryBuilder($builder->getQuery()),
        ]);
    }

    /**
     * Unpack
     *
     * @param \Webkernel\ORM\Serialization\Package $package
     * @return \Webkernel\ORM\Builder
     */
    protected function unpack(\Webkernel\ORM\Serialization\Package $package): \Webkernel\ORM\Builder
    {
        $this->setup();

        $builder = $package->get('model');
        $builder = $builder::on($package->get('connection'));

        $this->unpackEloquentBuilder($package->get('eloquent'), $builder);
        $this->unpackQueryBuilder($package->get('query'), $builder->getQuery());

        return $builder;
    }

    /**
     * init
     *
     * @return void
     */
    private function setup(): void
    {
        $serializeMorphableEager = fn ($value) => $this->serializeMorphableEager($value);
        $unserializeMorphableEager = fn ($value) => $this->unserializeMorphableEager($value);

        \Webkernel\ORM\Builder::macro('getAllGlobalScopes', function () {
            return $this->scopes;
        });

        \Webkernel\ORM\Relations\Relation::macro('importExtraParametersForSerialize', function (array $params) use ($unserializeMorphableEager) {
            foreach ($params as $key => $value) {
                if ($key == 'morphableEagerLoads' || $key == 'morphableEagerLoadCounts') {
                    $value = $unserializeMorphableEager($value);
                }

                $this->$key = $value;
            }
        });

        \Webkernel\ORM\Relations\Relation::macro('exportExtraParametersForSerialize', function () use ($serializeMorphableEager) {
            if ($this instanceof \Webkernel\ORM\Relations\MorphTo) {
                return [
                    'morphableEagerLoads' => $serializeMorphableEager($this->morphableEagerLoads),
                    'morphableEagerLoadCounts' => $serializeMorphableEager($this->morphableEagerLoadCounts),
                    'morphableConstraints' => $this->morphableConstraints,
                ];
            }

            if (
                $this instanceof HasOneOrMany
                && in_array(\Webkernel\ORM\Relations\Concerns\SupportsInverseRelations::class, class_uses(HasOneOrMany::class)) // @TODO: >= 11.22
            ) {
                return [
                    'inverseRelationship' => $this->inverseRelationship,
                ];
            }

            return null;
        });
    }

    /**
     * @param array $value
     * @return array
     */
    private function serializeMorphableEager(array $value): array
    {
        foreach ($value as $class => &$items) {
            foreach ($items as $relation => &$item) {
                if (! is_callable($item)) {
                    continue;
                }

                if (! method_exists($class, $relation)) {
                    throw new \RuntimeException("Serialization error. Does relation '{$relation}' exists in the model '{$class}' ?");
                }

                // ALT: $obj->cleanStaticConstraints($item['builder'], $obj->packQueryBuilder((new $class)->getQuery()->getQuery()));
                $eloquentBuilder = (new $class())->$relation()->getModel()->newQuery();
                $item($eloquentBuilder);

                $item = [
                    'eloquent' => $this->packEloquentBuilder($eloquentBuilder),
                    'builder' => $this->packQueryBuilder($eloquentBuilder->getQuery()),
                ];
            }
            unset($item);
        }
        unset($items);

        return $value;
    }

    /**
     * @param array $value
     * @return array
     */
    private function unserializeMorphableEager(array $value): array
    {
        foreach ($value as &$items) {
            foreach ($items as &$item) {
                if (! is_array($item)) {
                    continue;
                }

                $item = function ($query) use ($item) {
                    if ($query instanceof \Webkernel\ORM\Builder) {
                        $this->unpackEloquentBuilder($item['eloquent'], $query);
                        $this->unpackQueryBuilder($item['builder'], $query->getQuery());
                    } else {
                        $eloquent = $query->getQuery();
                        $this->unpackEloquentBuilder($item['eloquent'], $eloquent);
                        $this->unpackQueryBuilder($item['builder'], $query->getBaseQuery());
                    }
                };
            }
            unset($item);
        }
        unset($items);

        return $value;
    }
}
