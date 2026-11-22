<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Cache\Events;

class CacheLocksFlushing
{
    /**
     * The name of the cache store.
     *
     * @var string|null
     */
    public ?string $storeName;

    /**
     * Create a new event instance.
     *
     * @param  string|null  $storeName
     */
    public function __construct(?string $storeName)
    {
        $this->storeName = $storeName;
    }
}
