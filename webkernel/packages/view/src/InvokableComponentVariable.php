<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\View;

use ArrayIterator;
use Closure;
use \Webkernel\Utilities\Contracts\DeferringDisplayableValue;
use \Webkernel\Utilities\Enumerable;
use IteratorAggregate;
use Stringable;
use Traversable;

/**
 * @template-covariant TValue = mixed
 *
 * @mixin TValue
 */
class InvokableComponentVariable implements DeferringDisplayableValue, IteratorAggregate, Stringable
{
    /**
     * The callable instance to resolve the variable value.
     *
     * @var \Closure
     */
    protected $callable;

    /**
     * Create a new variable instance.
     *
     * @param  \Closure(): TValue  $callable
     */
    public function __construct(Closure $callable)
    {
        $this->callable = $callable;
    }

    /**
     * Resolve the displayable value that the class is deferring.
     *
     * @return \Webkernel\Utilities\Contracts\Htmlable|string
     */
    public function resolveDisplayableValue()
    {
        return $this->__invoke();
    }

    /**
     * Get an iterator instance for the variable.
     *
     * @return \ArrayIterator
     */
    public function getIterator(): Traversable
    {
        $result = $this->__invoke();

        return new ArrayIterator($result instanceof Enumerable ? $result->all() : $result);
    }

    /**
     * Dynamically proxy attribute access to the variable.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->__invoke()->{$key};
    }

    /**
     * Dynamically proxy method access to the variable.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->__invoke()->{$method}(...$parameters);
    }

    /**
     * Resolve the variable.
     *
     * @return TValue
     */
    public function __invoke()
    {
        return call_user_func($this->callable);
    }

    /**
     * Resolve the variable as a string.
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->__invoke();
    }
}
