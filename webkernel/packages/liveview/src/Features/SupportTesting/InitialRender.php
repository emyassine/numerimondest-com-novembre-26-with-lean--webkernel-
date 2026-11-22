<?php

namespace Webkernel\Liveview\Features\SupportTesting;

use Webkernel\Liveview\Drawer\Utils;

class InitialRender extends Render
{
    function __construct(
        protected RequestBroker $requestBroker,
    ) {}

    static function make($requestBroker, $name, $params = [], $fromQueryString = [], $cookies = [], $headers = [])
    {
        $instance = new static($requestBroker);

        return $instance->makeInitialRequest($name, $params, $fromQueryString, $cookies, $headers);
    }

    function makeInitialRequest($name, $params, $fromQueryString = [], $cookies = [], $headers = [])
    {
        $uri = '/livewire-unit-test-endpoint/'.str()->random(20);

        $this->registerRouteBeforeExistingRoutes($uri, function () use ($name, $params) {
            return \Webkernel\View\View::render('@liveview($name, $params)', [
                'name' => $name,
                'params' => $params,
            ]);
        });

        [$response, $componentInstance, $componentView] = $this->extractComponentAndTemplateView(function () use ($uri, $fromQueryString, $cookies, $headers) {
            return $this->requestBroker->temporarilyDisableExceptionHandlingAndMiddleware(function ($requestBroker) use ($uri, $fromQueryString, $cookies, $headers) {
                return $requestBroker->addHeaders($headers)->call('GET', $uri, $fromQueryString, $cookies);
            });
        });

        app('liveview')->flushState();

        $html = $response->getContent();

        // Preserve the resolved template for view assertions.
        $response->baseResponse->original = $componentView;

        $snapshot = Utils::extractAttributeDataFromHtml($html, 'wire:snapshot');
        $effects = Utils::extractAttributeDataFromHtml($html, 'wire:effects');

        return new ComponentState($componentInstance, $response, $componentView, $html, $snapshot, $effects);
    }

    private function registerRouteBeforeExistingRoutes($path, $closure)
    {
        // To prevent this route from overriding wildcard routes registered within the application,
        // We have to make sure that this route is registered before other existing routes.
        $livewireTestingRoute = new \Webkernel\Route\CurrentRoute(['GET', 'HEAD'], $path, $closure);

        $existingRoutes = app('router')->getRoutes();

        // Make an empty collection.
        $runningCollection = new \Webkernel\Route\RouteCollection;

        // Add this testing route as the first one.
        $runningCollection->add($livewireTestingRoute);

        // Now add the existing routes after it.
        foreach ($existingRoutes as $route) {
            $runningCollection->add($route);
        }

        // Now set this route collection as THE route collection for the app.
        app('router')->setRoutes($runningCollection);
    }
}
