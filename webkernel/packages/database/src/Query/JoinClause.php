<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
// Adapted from Laravel; see LICENSE-LARAVEL.md.

namespace Webkernel\Database\Query;

use Closure;

class JoinClause extends Builder
{
    /**
     * The type of join being performed.
     *
     * @var string
     */
    public $type;

    /**
     * The table the join clause is joining to.
     *
     * @var \Webkernel\Database\Contracts\Query\Expression|string
     */
    public $table;

    /**
     * The connection of the parent query builder.
     *
     * @var \Webkernel\Database\ConnectionInterface
     */
    protected $parentConnection;

    /**
     * The grammar of the parent query builder.
     *
     * @var \Webkernel\Database\Query\Grammars\Grammar
     */
    protected $parentGrammar;

    /**
     * The processor of the parent query builder.
     *
     * @var \Webkernel\Database\Query\Processors\Processor
     */
    protected $parentProcessor;

    /**
     * The class name of the parent query builder.
     *
     * @var string
     */
    protected $parentClass;

    /**
     * Create a new join clause instance.
     *
     * @param  \Webkernel\Database\Query\Builder  $parentQuery
     * @param  string  $type
     * @param  string  $table
     */
    public function __construct(Builder $parentQuery, $type, $table)
    {
        $this->type = $type;
        $this->table = $table;
        $this->parentClass = get_class($parentQuery);
        $this->parentGrammar = $parentQuery->getGrammar();
        $this->parentProcessor = $parentQuery->getProcessor();
        $this->parentConnection = $parentQuery->getConnection();

        parent::__construct(
            $this->parentConnection, $this->parentGrammar, $this->parentProcessor
        );
    }

    /**
     * Add an "on" clause to the join.
     *
     * On clauses can be chained, e.g.
     *
     *  $join->on('contacts.user_id', '=', 'users.id')
     *       ->on('contacts.info_id', '=', 'info.id')
     *
     * will produce the following SQL:
     *
     * on `contacts`.`user_id` = `users`.`id` and `contacts`.`info_id` = `info`.`id`
     *
     * @param  \Closure|\Webkernel\Database\Contracts\Query\Expression|string  $first
     * @param  string|null  $operator
     * @param  \Webkernel\Database\Contracts\Query\Expression|string|null  $second
     * @param  string  $boolean
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function on($first, $operator = null, $second = null, $boolean = 'and')
    {
        if ($first instanceof Closure) {
            return $this->whereNested($first, $boolean);
        }

        return $this->whereColumn($first, $operator, $second, $boolean);
    }

    /**
     * Add an "or on" clause to the join.
     *
     * @param  \Closure|\Webkernel\Database\Contracts\Query\Expression|string  $first
     * @param  string|null  $operator
     * @param  \Webkernel\Database\Contracts\Query\Expression|string|null  $second
     * @return \Webkernel\Database\Query\JoinClause
     */
    public function orOn($first, $operator = null, $second = null)
    {
        return $this->on($first, $operator, $second, 'or');
    }

    /**
     * Get a new instance of the join clause builder.
     *
     * @return \Webkernel\Database\Query\JoinClause
     */
    public function newQuery()
    {
        return new static($this->newParentQuery(), $this->type, $this->table);
    }

    /**
     * Create a new query instance for sub-query.
     *
     * @return \Webkernel\Database\Query\Builder
     */
    protected function forSubQuery()
    {
        return $this->newParentQuery()->newQuery();
    }

    /**
     * Create a new parent query instance.
     *
     * @return \Webkernel\Database\Query\Builder
     */
    protected function newParentQuery()
    {
        $class = $this->parentClass;

        return new $class($this->parentConnection, $this->parentGrammar, $this->parentProcessor);
    }
}
