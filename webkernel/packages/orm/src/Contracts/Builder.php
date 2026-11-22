<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\ORM\Contracts;

use Webkernel\Database\Contracts\Query\Builder as QueryBuilderContract;
use Webkernel\ORM\Builder as EloquentBuilder;

/**
 * Eloquent builder contract used for IDE support and ORM interoperability.
 *
 * @mixin EloquentBuilder
 */
interface Builder extends QueryBuilderContract
{
}
