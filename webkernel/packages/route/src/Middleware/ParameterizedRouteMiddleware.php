<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Route\Middleware;

use Webkernel\Route\Middleware\RouteMiddleware;
use Webkernel\Route\Request;

/** Middleware accepting parameters from a compiled declaration such as panel:system. */
interface ParameterizedRouteMiddleware extends RouteMiddleware
{
    public function handle(Request $request, callable $next, string ...$parameters): mixed;
}
