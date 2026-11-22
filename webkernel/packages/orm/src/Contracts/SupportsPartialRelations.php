<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\ORM\Contracts;

use Closure;
use Webkernel\ORM\Builder;

interface SupportsPartialRelations
{
    /**
     * @param $column string|null
     * @param $aggregate string|Closure|null
     * @param $relation string|null
     * @return $this
     */
    public function ofMany($column = 'id', $aggregate = 'MAX', $relation = null);

    public function isOneOfMany();

    /** @return Builder|void */
    public function getOneOfManySubQuery();
}
