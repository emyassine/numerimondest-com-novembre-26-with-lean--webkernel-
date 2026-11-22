<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Queue\Attributes;

use Attribute;
use UnitEnum;

use function \Webkernel\Utilities\enum_value;

#[Attribute(Attribute::TARGET_CLASS)]
class Queue
{
    /**
     * Create a new attribute instance.
     *
     * @param  UnitEnum|string  $queue
     */
    public function __construct(public UnitEnum|string $queue)
    {
        $this->queue = enum_value($queue);
    }
}
