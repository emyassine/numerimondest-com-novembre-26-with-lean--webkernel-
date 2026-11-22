<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Route\Middleware;

use Webkernel\Route\Request;

/** HTTP middleware contract for route and panel middleware. */
interface RouteMiddleware
{
    /** @param callable(): mixed $next */
    public function handle(Request $request, callable $next): mixed;
}
