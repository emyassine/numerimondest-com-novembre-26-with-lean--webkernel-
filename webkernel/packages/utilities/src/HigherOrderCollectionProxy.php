<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
// Adapted from Laravel; see LICENSE-LARAVEL.md.

namespace Webkernel\Utilities;

/**
 * @template TMethod of string
 * @template TValue
 * @template TCollection of \Webkernel\Utilities\Enumerable<array-key, TValue>
 *
 * @mixin TValue
 */
class HigherOrderCollectionProxy
{
    /**
     * The collection being operated on.
     *
     * @var TCollection
     */
    protected $collection;

    /**
     * The method being proxied.
     *
     * @var TMethod
     */
    protected $method;

    /**
     * Create a new proxy instance.
     *
     * @param  TCollection  $collection
     * @param  TMethod  $method
     */
    public function __construct(Enumerable $collection, $method)
    {
        $this->method = $method;
        $this->collection = $collection;
    }

    /**
     * Proxy accessing an attribute onto the collection items.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->collection->{$this->method}(function ($value) use ($key) {
            return is_array($value) ? $value[$key] : $value->{$key};
        });
    }

    /**
     * Proxy a method call onto the collection items.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->collection->{$this->method}(function ($value) use ($method, $parameters) {
            return is_string($value)
                ? $value::{$method}(...$parameters)
                : $value->{$method}(...$parameters);
        });
    }
}
