<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Container;

use Closure;
use LogicException;
use Webkernel\Container\Container;

/** Resolves the existing static package APIs through Webkernel's service lifetimes. */
abstract class Facade
{
    abstract protected static function getFacadeAccessor();

    public static function getFacadeRoot(): object
    {
        $accessor = static::getFacadeAccessor();
        if (! is_string($accessor) || $accessor === '') {
            throw new LogicException('A facade must declare its service identifier.');
        }
        $container = Container::getInstance();
        $container->singletonIf($accessor);
        return $container->make($accessor);
    }

    public static function resolved(Closure $callback): void
    {
        $callback(static::getFacadeRoot());
    }

    public static function swap(object $instance): void
    {
        Container::getInstance()->instance(static::getFacadeAccessor(), $instance);
    }

    public static function __callStatic(string $method, array $arguments): mixed
    {
        return static::getFacadeRoot()->{$method}(...$arguments);
    }
}
