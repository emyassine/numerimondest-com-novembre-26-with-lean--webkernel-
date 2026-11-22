<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Cache\Events;

class RetrievingManyKeys extends CacheEvent
{
    /**
     * The keys that are being retrieved.
     *
     * @var array
     */
    public $keys;

    /**
     * Create a new event instance.
     *
     * @param  string|null  $storeName
     * @param  array  $keys
     * @param  array  $tags
     */
    public function __construct($storeName, $keys, array $tags = [])
    {
        parent::__construct($storeName, $keys[0] ?? '', $tags);

        $this->keys = $keys;
    }
}
