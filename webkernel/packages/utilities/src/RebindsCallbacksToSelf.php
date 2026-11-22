<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
// Adapted from Laravel; see LICENSE-LARAVEL.md.

namespace Webkernel\Utilities;

use Closure;
use ReflectionFunction;

trait RebindsCallbacksToSelf
{
    /**
     * Binds the provided callback to the class instance.
     *
     * @throws \ReflectionException
     */
    protected function bindCallbackToSelf(Closure $callback): ?Closure
    {
        $reflector = new ReflectionFunction($callback);

        // We only want to rebind anonymous functions.
        if ($reflector->isAnonymous()) {
            if ($reflector->isStatic()) {
                // Static functions are bound without $this.
                $callback = $callback->bindTo(null, static::class);
            } else {
                // Non-static functions are bound to $this.
                $callback = $callback->bindTo($this, static::class);
            }
        }

        return $callback;
    }
}
