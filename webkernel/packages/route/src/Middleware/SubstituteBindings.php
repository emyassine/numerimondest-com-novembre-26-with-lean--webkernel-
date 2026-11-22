<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Route\Middleware;

use Closure;
use \Webkernel\Route\Contracts\Registrar;
use \Webkernel\ORM\ModelNotFoundException;

class SubstituteBindings
{
    /**
     * The router instance.
     *
     * @var \Webkernel\Route\Contracts\Registrar
     */
    protected $router;

    /**
     * Create a new bindings substitutor.
     *
     * @param  \Webkernel\Route\Contracts\Registrar  $router
     */
    public function __construct(Registrar $router)
    {
        $this->router = $router;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Webkernel\HttpFoundation\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @throws \Webkernel\ORM\ModelNotFoundException
     */
    public function handle($request, Closure $next)
    {
        $route = $request->route();

        try {
            $this->router->substituteBindings($route);
            $this->router->substituteImplicitBindings($route);
        } catch (ModelNotFoundException $exception) {
            if ($route->getMissing()) {
                return $route->getMissing()($request, $exception);
            }

            throw $exception;
        }

        return $next($request);
    }
}
