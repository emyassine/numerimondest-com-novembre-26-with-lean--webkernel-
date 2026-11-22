<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
// Adapted from AnourValar Eloquent Serialize and Laravel Serializable Closure; see the accompanying LICENSE-*.md files.
namespace Webkernel\ORM\Serialization\Grammars;

trait QueryBuilderGrammar
{
    /**
     * Serialize state for \Webkernel\Database\Query\Builder
     *
     * @param \Webkernel\Database\Query\Builder $builder
     * @return array
     */
    protected function packQueryBuilder(\Webkernel\Database\Query\Builder $builder): array
    {
        return array_filter([
            'bindings' => $builder->bindings,
            'aggregate' => $builder->aggregate,
            'columns' => $builder->columns,
            'distinct' => $builder->distinct,
            'from' => $builder->from,
            'wheres' => $this->packWheres($builder->wheres),
            'groups' => $builder->groups,
            'havings' => $this->packWheres($builder->havings),
            'groupLimit' => $builder->groupLimit ?? null,
            'orders' => $builder->orders,
            'limit' => $builder->limit,
            'offset' => $builder->offset,
            'unions' => $this->packUnions($builder->unions),
            'unionLimit' => $builder->unionLimit,
            'unionOffset' => $builder->unionOffset,
            'unionOrders' => $builder->unionOrders,
            'lock' => $builder->lock,
            'useWritePdo' => $builder->useWritePdo,

            'joins' => $this->packJoins($builder->joins), // must be the last
        ], fn ($item) => isset($item));
    }

    /**
     * @param array $data
     * @param \Webkernel\Database\Query\Builder $builder
     * @return \Webkernel\Database\Query\Builder
     */
    protected function unpackQueryBuilder(array $data, \Webkernel\Database\Query\Builder $builder): \Webkernel\Database\Query\Builder
    {
        foreach ($data as $key => $value) {
            if (in_array($key, ['wheres', 'havings'])) {
                $value = $this->unpackWheres($value, $builder);
            }

            if ($key == 'unions') {
                $value = $this->unpackUnions($value);
            }

            if ($key == 'joins') {
                $value = $this->unpackJoins($value, $builder);
            }

            if (is_array($builder->$key) && is_array($value)) {
                $builder->$key = array_merge_recursive($builder->$key, $value);
            } else {
                $builder->$key = $value;
            }
        }

        return $builder;
    }

    /**
     * @param mixed $wheres
     * @return mixed
     */
    private function packWheres($wheres)
    {
        if (is_null($wheres)) {
            return $wheres;
        }

        foreach ($wheres as &$item) {
            if (isset($item['query'])) {
                $item['query'] = $this->packQueryBuilder($item['query']);
            }
        }
        unset($item);

        return $wheres;
    }

    /**
     * @param mixed $unions
     * @return mixed
     */
    private function packUnions($unions)
    {
        if (! is_array($unions)) {
            return $unions;
        }

        foreach ($unions as &$item) {
            if (isset($item['query'])) {
                $item['query'] = $this->pack($item['query']);
            }
        }
        unset($item);

        return $unions;
    }

    /**
     * @param mixed $joins
     * @return mixed
     */
    private function packJoins($joins)
    {
        if (! is_array($joins)) {
            return $joins;
        }

        foreach ($joins as &$item) {
            $item = array_replace(
                ['class' => get_class($item), 'type' => $item->type, 'table' => $item->table],
                $this->packQueryBuilder($item)
            );
        }
        unset($item);

        return $joins;
    }

    /**
     * @param mixed $wheres
     * @param \Webkernel\Database\Query\Builder $builder
     * @return mixed
     */
    private function unpackWheres($wheres, \Webkernel\Database\Query\Builder $builder)
    {
        if (is_null($wheres)) {
            return $wheres;
        }

        foreach ($wheres as &$item) {
            if (isset($item['query'])) {
                $item['query'] = $this->unpackQueryBuilder($item['query'], $builder->newQuery());
            }
        }
        unset($item);

        return $wheres;
    }

    /**
     * @param mixed $unions
     * @return mixed
     */
    private function unpackUnions($unions)
    {
        if (! is_array($unions)) {
            return $unions;
        }

        foreach ($unions as &$item) {
            if (isset($item['query'])) {
                $item['query'] = $this->unpack($item['query']);
            }
        }
        unset($item);

        return $unions;
    }

    /**
     * @param mixed $joins
     * @param \Webkernel\Database\Query\Builder $builder
     * @return mixed
     */
    private function unpackJoins($joins, \Webkernel\Database\Query\Builder $builder)
    {
        if (! is_array($joins)) {
            return $joins;
        }

        foreach ($joins as &$item) {
            $parentQuery = new ($item['class'])($builder, $item['type'], $item['table']);
            unset($item['class'], $item['type'], $item['table']);

            $item = $this->unpackQueryBuilder($item, $parentQuery);
        }
        unset($item);

        return $joins;
    }
}
