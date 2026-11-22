<?php

namespace Webkernel\Liveview\Mechanisms\HandleRequests;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Webkernel\Config\Config;
use Webkernel\Route\Route;
use Webkernel\Liveview\Providers\WebMiddleware;
use Webkernel\Liveview\Features\SupportScriptsAndAssets\SupportScriptsAndAssets;
use Webkernel\Liveview\Features\SupportReactiveProps\SupportReactiveProps;
use Webkernel\Liveview\Mechanisms\HandleComponents\HandleComponents;
use Webkernel\Liveview\Mechanisms\HandleRequests\EndpointResolver;
use Webkernel\Liveview\Exceptions\PayloadTooLargeException;
use Webkernel\Liveview\Exceptions\TooManyComponentsException;

use Webkernel\Liveview\Mechanisms\Mechanism;
use Webkernel\Liveview\Webkernel\Runtime;

use function Webkernel\Liveview\trigger;

class HandleRequests extends Mechanism
{
    protected $updateRoute;

    protected $shouldPropagateExceptions = false;

    function boot()
    {
        // Register the default route immediately (before routes files load)
        // so it's positioned before any catch-all routes.
        if (! $this->updateRoute && ! $this->updateRouteExists()) {
            $this->setUpdateRoute(function ($handle, $path) {
                return Route::post($path, $handle)
                    ->middleware([WebMiddleware::class, RequireLivewireHeaders::class])
                    ->name('default-liveview.update');
            });
        }

        $this->skipRequestPayloadTamperingMiddleware();
    }

    protected function updateRouteExists()
    {
        return $this->findUpdateRoute() !== null;
    }

    function getUriPrefix()
    {
        return EndpointResolver::prefix();
    }

    function getUpdateUri()
    {
        // When routes are cached, $this->updateRoute may be null because
        // setUpdateRoute() was never called (the route already existed).
        // In this case, find the route from the router.
        $route = $this->updateRoute ?? $this->findUpdateRoute();

        if (is_array($route)) { return $route['uri']; }
        if (is_object($route) && method_exists($route, 'uri')) { return $route->uri(); }

        return EndpointResolver::updatePath();
    }

    protected function findUpdateRoute()
    {
        $default = null;
        foreach (Route::list() as $route) {
            if (str_ends_with($route['name'], 'liveview.update')) {
                if ($route['name'] === 'default-liveview.update') {
                    $default = $route;
                    continue;
                }

                return $route;
            }
        }

        return $default;
    }

    function skipRequestPayloadTamperingMiddleware()
    {
        // Webkernel does not mutate request input globally.
    }

    function setUpdateRoute($callback)
    {
        $route = $callback([self::class, 'handleUpdate'], EndpointResolver::updatePath());

        // Ensure the route includes the session/CSRF middleware group. Webkernel
        // names the concrete class because its route cache stores middleware names.
        if (! in_array(WebMiddleware::class, $route->middleware_list(), true)) {
            $route->middleware(WebMiddleware::class);
        }

        // Ensure the header guard middleware is always present, even on custom routes.
        // Only append if its not exists on current middleware stack
        if (! in_array(RequireLivewireHeaders::class, $route->middleware_list(), true)) {
            $route->middleware(RequireLivewireHeaders::class);
        }

        // Append `liveview.update` to the existing name, if any.
        if (! str_ends_with($route->resolved_name(), 'liveview.update')) {
            $route->append_name('liveview.update');
        }

        $this->updateRoute = $route;
    }

    public function resetRouteRegistration(): void
    {
        $this->updateRoute = null;
    }

    function isLivewireRequest()
    {
        return Route::currentRequest()->hasHeader('X-Livewire');
    }

    function isLivewireRoute()
    {
        // @todo: Rename this back to `isLivewireRequest` once the need for it in tests has been fixed.
        $routeName = Route::currentName();

        if (! $routeName) return false;

        /*
         * Check to see if route name ends with `liveview.update`, as if
         * a custom update route is used and they add a name, then when
         * we call `->name('liveview.update')` on the route it will
         * suffix the existing name with `liveview.update`.
         */
        return str_ends_with($routeName, 'liveview.update');
    }

    function temporarilyPropagateExceptions($callback)
    {
        $cachedShouldPropagateExceptions = $this->shouldPropagateExceptions;

        $this->shouldPropagateExceptions = true;

        try {
            return $callback();
        } finally {
            $this->shouldPropagateExceptions = $cachedShouldPropagateExceptions;
        }
    }

    function handleUpdate()
    {
        // When a custom update route is registered, reject requests that arrive
        // via the default route. This prevents attackers from bypassing middleware
        // (e.g. auth, tenant scoping) added to the custom route.
        if (Route::currentName() === 'default-liveview.update'
            && ($this->findUpdateRoute()['name'] ?? null) !== 'default-liveview.update') {
            throw new NotFoundHttpException();
        }

        $request = Route::currentRequest();

        // Check payload size limit...
        $maxSize = Config::get('liveview.payload.max_size');

        if ($maxSize !== null) {
            $size = strlen($request->content());

            if ($size > $maxSize) {
                throw new PayloadTooLargeException($size, $maxSize);
            }
        }

        $requestPayload = $request->input('components');

        if (! is_array($requestPayload) || empty($requestPayload)) {
            throw new NotFoundHttpException();
        }

        foreach ($requestPayload as $component) {
            if (! is_array($component)
                || ! is_string($component['snapshot'] ?? null)
                || ! is_array($component['updates'] ?? null)
                || ! is_array($component['calls'] ?? null)
            ) {
                throw new NotFoundHttpException();
            }
        }

        // Check max components limit...
        $maxComponents = Config::get('liveview.payload.max_components');

        if ($maxComponents !== null && count($requestPayload) > $maxComponents) {
            throw new TooManyComponentsException(count($requestPayload), $maxComponents);
        }

        $finish = trigger('request', $requestPayload);

        $requestPayload = $finish($requestPayload);

        $componentResponses = [];

        foreach ($requestPayload as $componentPayload) {
            $snapshot = json_decode($componentPayload['snapshot'], associative: true);
            $updates = $componentPayload['updates'];
            $calls = $componentPayload['calls'];

            if (! is_array($snapshot)
                || ! is_array($snapshot['data'] ?? null)
                || ! is_array($snapshot['memo'] ?? null)
                || ! is_string($snapshot['checksum'] ?? null)
                || ! is_string($snapshot['memo']['id'] ?? null)
                || ! is_string($snapshot['memo']['name'] ?? null)
            ) {
                throw new NotFoundHttpException();
            }

            $components = Runtime::resolve(HandleComponents::class);
            $components->validateUpdatePayload($updates, $calls);
            $components->verifySnapshot($snapshot);

            // If this is a reactive child whose props didn't change,
            // skip its entire lifecycle (hydrate, render, dehydrate)...
            if (empty($updates) && SupportReactiveProps::shouldSkipUpdate($snapshot, $calls)) {
                $componentResponses[] = [
                    'skip' => true,
                    'id' => $snapshot['memo']['id'],
                ];

                continue;
            }

            try {
                [ $snapshot, $effects ] = $components->update(
                    $snapshot,
                    $updates,
                    $calls,
                    payload_validated: true,
                    snapshot_verified: true,
                );
            } catch (\TypeError $e) {
                if (function_exists('report')) { report($e); }

                if (Config::get('app.debug', false) || $this->shouldPropagateExceptions) throw $e;

                throw new HttpException(419);
            }

            $componentResponses[] = [
                'snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR),
                'effects' => $effects,
            ];
        }

        $responsePayload = [
            'components' => $componentResponses,
            'assets' => SupportScriptsAndAssets::getAssets(),
        ];

        $finish = trigger('response', $responsePayload);

        $payload = $finish($responsePayload);

        // When wire:stream is used, headers are sent early by SupportStreaming::ensureStreamResponseStarted().
        // The streaming content has already been output via echo/flush in SupportStreaming::streamContent().
        // This final JSON response contains the component snapshot and must be output without attempting
        // to send additional headers, which would cause "headers already sent" warnings (since Symfony 7.2.7).
        $json = json_encode($payload, JSON_THROW_ON_ERROR);
        if (! headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }

        return $json;
    }
}
