<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Route\Controllers;

use Closure;
use \Webkernel\Utilities\Arr;

/**
 * @phpstan-type NextClosure \Closure(\Webkernel\HttpFoundation\Request): \Symfony\Component\HttpFoundation\Response
 */
class Middleware
{
    /**
     * Create a new controller middleware definition.
     *
     * @param  (\Closure(\Webkernel\HttpFoundation\Request, NextClosure): \Symfony\Component\HttpFoundation\Response)|string|array  $middleware
     * @param  array<string>|null  $only
     * @param  array<string>|null  $except
     */
    public function __construct(public Closure|string|array $middleware, public ?array $only = null, public ?array $except = null)
    {
    }

    /**
     * Specify the only controller methods the middleware should apply to.
     *
     * @param  array|string  $only
     * @return $this
     */
    public function only(array|string $only)
    {
        $this->only = Arr::wrap($only);

        return $this;
    }

    /**
     * Specify the controller methods the middleware should not apply to.
     *
     * @param  array|string  $except
     * @return $this
     */
    public function except(array|string $except)
    {
        $this->except = Arr::wrap($except);

        return $this;
    }
}
