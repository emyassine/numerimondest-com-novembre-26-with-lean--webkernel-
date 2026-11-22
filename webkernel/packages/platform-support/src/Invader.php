<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Platform\Support;

use Closure;

/** @mixin object */
final class Invader
{
    public function __construct(private object $object)
    {
    }

    public static function of(object $object): self
    {
        return new self($object);
    }

    public function __get(string $name): mixed
    {
        return Closure::bind(
            function () use ($name): mixed {
                return $this->{$name};
            },
            $this->object,
            $this->object,
        )();
    }

    public function __set(string $name, mixed $value): void
    {
        Closure::bind(
            function () use ($name, $value): void {
                $this->{$name} = $value;
            },
            $this->object,
            $this->object,
        )();
    }

    public function __call(string $name, array $arguments): mixed
    {
        return Closure::bind(
            function () use ($name, $arguments): mixed {
                return $this->{$name}(...$arguments);
            },
            $this->object,
            $this->object,
        )();
    }
}
