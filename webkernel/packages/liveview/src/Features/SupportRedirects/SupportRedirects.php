<?php

namespace Webkernel\Liveview\Features\SupportRedirects;

use Webkernel\Route\Route;
use Webkernel\Route\Redirector as WebkernelRedirector;
use Webkernel\Liveview\Mechanisms\HandleRequests\HandleRequests;
use Webkernel\Liveview\Mechanisms\HandleRouting\LivewirePageController;
use Webkernel\Liveview\ComponentHook;
use Webkernel\Liveview\Component;
use function Webkernel\Liveview\on;

class SupportRedirects extends ComponentHook
{
    public static $redirectorCacheStack = [];
    public static $atLeastOneMountedComponentHasRedirected = false;

    public static function provide()
    {
        // Wait until all components have been processed...
        on('response', function ($response) {
            // If there was no redirect on a subsequent component update, clear flash session data.
            if (! static::$atLeastOneMountedComponentHasRedirected && app()->has('session.store')) {
                session()->forget(session()->get('_flash.new'));
            }
        });

        on('flush-state', function () {
            static::$atLeastOneMountedComponentHasRedirected = false;
            static::$redirectorCacheStack = [];
        });
    }

    public function boot()
    {
        // Scope the application redirector to this component.
        static::$redirectorCacheStack[] = app('redirect');

        app()->bind('redirect', function () {
            $redirector = app(Redirector::class)->component($this->component);

            if (app()->has('session.store')) {
                $redirector->setSession(app('session.store'));
            }

            return $redirector;
        });
    }

    public function dehydrate($context)
    {
        // Put the old redirector back into the container.
        $redirector = array_pop(static::$redirectorCacheStack) ?? app(WebkernelRedirector::class);
        app()->instance('redirect', $redirector);

        $to = $this->storeGet('redirect');
        $usingNavigate = $this->storeGet('redirectUsingNavigate');

        if (is_subclass_of($to, Component::class)) {
            $to = static::resolveComponentUrl($to);
        }

        if ($to && ! app(HandleRequests::class)->isLivewireRequest()) {
            abort(redirect($to));
        }

        if (! $to) return;

        $context->addEffect('redirect', $to);
        $usingNavigate && $context->addEffect('redirectUsingNavigate', true);

        if (! $context->isMounting()) {
            static::$atLeastOneMountedComponentHasRedirected = true;
        }
    }

    public static function resolveComponentUrl(string $componentClass): string
    {
        foreach (Route::bindings() as $route) {
            if ($route->action() !== LivewirePageController::class) { continue; }
            if (($route->extra()['liveview_component'] ?? null) !== $componentClass) { continue; }

            return Route::url($route->resolved_name());
        }

        throw new \InvalidArgumentException("Unable to resolve URL for Livewire component [{$componentClass}]. Make sure the component is registered as a route.");
    }
}
