<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Cache\Events;

class WritingManyKeys extends CacheEvent
{
    /**
     * The keys that are being written.
     *
     * @var mixed
     */
    public $keys;

    /**
     * The value that is being written.
     *
     * @var mixed
     */
    public $values;

    /**
     * The number of seconds the keys should be valid.
     *
     * @var int|null
     */
    public $seconds;

    /**
     * Create a new event instance.
     *
     * @param  string|null  $storeName
     * @param  array  $keys
     * @param  array  $values
     * @param  int|null  $seconds
     * @param  array  $tags
     */
    public function __construct($storeName, $keys, $values, $seconds = null, $tags = [])
    {
        parent::__construct($storeName, $keys[0], $tags);

        $this->keys = $keys;
        $this->values = $values;
        $this->seconds = $seconds;
    }
}
