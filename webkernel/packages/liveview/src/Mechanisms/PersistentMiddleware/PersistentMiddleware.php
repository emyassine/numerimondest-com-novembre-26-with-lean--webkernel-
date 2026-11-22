<?php

namespace Webkernel\Liveview\Mechanisms\PersistentMiddleware;

use Webkernel\Liveview\Mechanisms\Mechanism;
use function Webkernel\Liveview\on;
use Webkernel\Liveview\Mechanisms\HandleRequests\HandleRequests;
use Webkernel\Liveview\Contracts\PerSnapshotMiddleware;
use Webkernel\Liveview\Webkernel\Runtime;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Webkernel\Models\Model;
use Webkernel\Route\Request;
use Webkernel\Route\Route as WebkernelRoute;

class PersistentMiddleware extends Mechanism
{
    protected static $persistentMiddleware = [
        \Webkernel\Liveview\Providers\WebMiddleware::class,
    ];

    protected $path;
    protected $method;
    protected $lastMiddlewareRoute;
    protected $resolvedRouteModels = [];

    function boot()
    {
        if (class_exists(\Webkernel\Platform\Http\Middleware\SetUpPanel::class)) {
            static::addPersistentMiddleware([\Webkernel\Platform\Http\Middleware\SetUpPanel::class]);
        }

        on('dehydrate', function ($component, $context) {
            [$path, $method] = $this->extractPathAndMethodFromRequest();

            $context->addMemo('path', $path);
            $context->addMemo('method', $method);
        });

        on('snapshot-verified', function ($snapshot) {
            // Only apply middleware to requests hitting the Livewire update endpoint, and not any fake requests such as a test.
            if (! Runtime::resolve(HandleRequests::class)->isLivewireRoute()) return;

            $this->extractPathAndMethodFromSnapshot($snapshot);

            $this->applyPersistentMiddleware();
        });

        on('flush-state', function() {
            // Only flush these at the end of a full request, so that child components have access to this data.
            $this->path = null;
            $this->method = null;
            $this->lastMiddlewareRoute = null;
            $this->resolvedRouteModels = [];
        });
    }

    function addPersistentMiddleware($middleware)
    {
        static::$persistentMiddleware = array_values(array_unique(array_merge(
            static::$persistentMiddleware,
            array_values(array_filter((array) $middleware, 'is_string')),
        )));
    }

    function setPersistentMiddleware($middleware)
    {
        static::$persistentMiddleware = array_values(array_unique(array_filter((array) $middleware, 'is_string')));
    }

    function getPersistentMiddleware()
    {
        return static::$persistentMiddleware;
    }

    function getResolvedRouteModel($class, $key)
    {
        return $this->resolvedRouteModels[$class.':'.$key] ?? null;
    }

    protected function extractPathAndMethodFromRequest()
    {
        if (Runtime::resolve(HandleRequests::class)->isLivewireRoute()) {
            return [$this->path, $this->method];
        }

        return [request()->path(), request()->method()];
    }

    protected function extractPathAndMethodFromSnapshot($snapshot)
    {
        if (
            ! isset($snapshot['memo']['path'])
            || ! isset($snapshot['memo']['method'])
        ) return;

        // Store these locally, so dynamically added child components can use this data.
        $this->path = $snapshot['memo']['path'];
        $this->method = $snapshot['memo']['method'];
    }

    protected function applyPersistentMiddleware()
    {
        $routeKey = $this->method . '|' . $this->path;

        $sameRoute = $this->lastMiddlewareRoute === $routeKey;

        $request = $this->makeFakeRequest();

        $matchedRoute = WebkernelRoute::matchRequest($request);
        if ($matchedRoute === null) { throw new NotFoundHttpException(); }
        $middleware = $this->middlewareForSnapshot(
            WebkernelRoute::expandMiddleware($matchedRoute->middleware()),
            $sameRoute,
        );
        if ($middleware === []) { return; }

        $route = WebkernelRoute::dispatchMiddlewareForRequest(
            $request,
            $middleware,
            $matchedRoute,
            resolveParameters: ! $sameRoute,
        );
        if ($route === null) {
            $status = http_response_code();
            throw new HttpException(is_int($status) && $status >= 300 ? $status : 403, 'The original route rejected this Livewire request.');
        }

        if (! $sameRoute) {
            $this->lastMiddlewareRoute = $routeKey;
        }

        // After middleware has run (e.g. SubstituteBindings), collect any
        // resolved model instances from the route parameters so that
        // ModelSynth can reuse them instead of re-querying the database.
        if ($route !== null) {
            foreach ($route->parameters() as $parameter) {
                if ($parameter instanceof Model && ($modelKey = $parameter->getKey()) !== null) {
                    $key = get_class($parameter).':'.$modelKey;
                    $this->resolvedRouteModels[$key] = $parameter;
                }
            }
        }
    }

    protected function makeFakeRequest()
    {
        $originalPath = $this->formatPath($this->path);
        $originalMethod = $this->method;
        $request = request();
        if (! $request instanceof Request) {
            throw new \RuntimeException('Livewire persistent middleware requires a Webkernel request.');
        }
        $query = parse_url($request->uri(), PHP_URL_QUERY);
        $uri = $originalPath.($query === null || $query === false || $query === '' ? '' : '?'.$query);

        return $request->withPathAndMethod($uri, $originalMethod);
    }

    protected function formatPath($path)
    {
        return '/' . ltrim($path, '/');
    }

    protected function filterMiddlewareByPersistentMiddleware($middleware)
    {
        $persistent = array_fill_keys(array_map(
            static fn (string $value): string => explode(':', $value, 2)[0],
            array_filter(static::$persistentMiddleware, 'is_string'),
        ), true);

        return array_values(array_filter($middleware, static function (mixed $value) use ($persistent): bool {
            if (! is_string($value)) {
                return false;
            }

            $class = explode(':', $value, 2)[0];

            return isset($persistent[$class])
                || (class_exists($class) && is_subclass_of($class, PerSnapshotMiddleware::class));
        }));
    }

    /** Re-run marked authorization middleware on every snapshot sharing a route. */
    protected function middlewareForSnapshot(array $middleware, bool $sameRoute): array
    {
        $middleware = $this->filterMiddlewareByPersistentMiddleware($middleware);
        if (! $sameRoute) {
            return $middleware;
        }

        return array_values(array_filter(
            $middleware,
            static function (mixed $declaration): bool {
                if (! is_string($declaration)) { return false; }
                $class = explode(':', $declaration, 2)[0];

                return class_exists($class) && is_subclass_of($class, PerSnapshotMiddleware::class);
            },
        ));
    }
}
