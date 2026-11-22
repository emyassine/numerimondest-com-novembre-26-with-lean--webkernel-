<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Route\Contracts;

interface Registrar
{
    /**
     * Register a new GET route with the router.
     *
     * @param  string  $uri
     * @param  array|string|callable  $action
     * @return \Webkernel\Route\CurrentRoute
     */
    public function get($uri, $action);

    /**
     * Register a new POST route with the router.
     *
     * @param  string  $uri
     * @param  array|string|callable  $action
     * @return \Webkernel\Route\CurrentRoute
     */
    public function post($uri, $action);

    /**
     * Register a new PUT route with the router.
     *
     * @param  string  $uri
     * @param  array|string|callable  $action
     * @return \Webkernel\Route\CurrentRoute
     */
    public function put($uri, $action);

    /**
     * Register a new DELETE route with the router.
     *
     * @param  string  $uri
     * @param  array|string|callable  $action
     * @return \Webkernel\Route\CurrentRoute
     */
    public function delete($uri, $action);

    /**
     * Register a new PATCH route with the router.
     *
     * @param  string  $uri
     * @param  array|string|callable  $action
     * @return \Webkernel\Route\CurrentRoute
     */
    public function patch($uri, $action);

    /**
     * Register a new OPTIONS route with the router.
     *
     * @param  string  $uri
     * @param  array|string|callable  $action
     * @return \Webkernel\Route\CurrentRoute
     */
    public function options($uri, $action);

    /**
     * Register a new route with the given verbs.
     *
     * @param  array|string  $methods
     * @param  string  $uri
     * @param  array|string|callable  $action
     * @return \Webkernel\Route\CurrentRoute
     */
    public function match($methods, $uri, $action);

    /**
     * Route a resource to a controller.
     *
     * @param  string  $name
     * @param  string  $controller
     * @param  array  $options
     * @return \Webkernel\Route\PendingResourceRegistration
     */
    public function resource($name, $controller, array $options = []);

    /**
     * Create a route group with shared attributes.
     *
     * @param  array  $attributes
     * @param  \Closure|string  $routes
     * @return void
     */
    public function group(array $attributes, $routes);

    /**
     * Substitute the route bindings onto the route.
     *
     * @param  \Webkernel\Route\CurrentRoute  $route
     * @return \Webkernel\Route\CurrentRoute
     */
    public function substituteBindings($route);

    /**
     * Substitute the implicit Eloquent model bindings for the route.
     *
     * @param  \Webkernel\Route\CurrentRoute  $route
     * @return void
     */
    public function substituteImplicitBindings($route);
}
