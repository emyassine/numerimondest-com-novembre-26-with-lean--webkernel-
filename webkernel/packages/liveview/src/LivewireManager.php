<?php

namespace Webkernel\Liveview;

use Webkernel\Liveview\Mechanisms\PersistentMiddleware\PersistentMiddleware;
use Webkernel\Liveview\Mechanisms\HandleRequests\HandleRequests;
use Webkernel\Liveview\Mechanisms\HandleSynths\HandleSynths;
use Webkernel\Liveview\Mechanisms\HandleComponents\HandleComponents;
use Webkernel\Liveview\Mechanisms\HandleComponents\ComponentContext;
use Webkernel\Liveview\Mechanisms\FrontendAssets\FrontendAssets;
use Webkernel\Liveview\Features\SupportTesting\Testable;
use Webkernel\Liveview\Features\SupportLazyLoading\SupportLazyLoading;
use Webkernel\Liveview\Features\SupportAutoInjectedAssets\SupportAutoInjectedAssets;
use Webkernel\Liveview\Webkernel\Runtime;
use Webkernel\View\View;

class LivewireManager
{
    public static $v4 = true;

    public function __construct()
    {
        if (class_exists(\Webkernel\Route\Route::class)) {
            \Webkernel\Route\Route::appendMiddlewareGroup('web', [\Webkernel\Liveview\Providers\WebMiddleware::class]);
        }
    }

    function component($name, $class = null)
    {
        $this->addComponent($name, class: $class);
    }

    function addComponent($name, $viewPath = null, $class = null)
    {
        Runtime::resolve('liveview.finder')->addComponent($name, class: $class, viewPath: $viewPath);
    }

    function addLocation($viewPath = null, $classNamespace = null)
    {
        return Runtime::resolve('liveview.finder')->addLocation(classNamespace: $classNamespace, viewPath: $viewPath);
    }

    function addNamespace($namespace, $viewPath = null, $classNamespace = null, $classPath = null, $classViewPath = null)
    {
        return Runtime::resolve('liveview.finder')->addNamespace($namespace, classNamespace: $classNamespace, viewPath: $viewPath, classPath: $classPath, classViewPath: $classViewPath);
    }

    function componentHook($hook)
    {
        ComponentHookRegistry::register($hook);
    }

    function propertySynthesizer($synth)
    {
        Runtime::resolve(HandleSynths::class)->registerSynth($synth);
    }

    function directive($name, $callback)
    {
        View::directive($name, $callback);
    }

    function precompiler($callback)
    {
        View::prepareViewsForCompilationUsing($callback);
    }

    function prepareViewsForCompilationUsing(callable $callback)
    {
        Runtime::resolve('liveview.compiler')->prepareViewsForCompilationUsing($callback);
    }

    function new($name, $id = null)
    {
        return Runtime::resolve('liveview.factory')->create($name, $id);
    }

    function exists($componentNameOrClass)
    {
        return Runtime::resolve('liveview.factory')->exists($componentNameOrClass);
    }

    function resolveMissingComponent($resolver)
    {
        return Runtime::resolve('liveview.factory')->resolveMissingComponent($resolver);
    }

    function mount($name, $params = [], $key = null, $slots = [])
    {
        return Runtime::mount($name, $params, $key, $slots);
    }

    function snapshot($component, $context = null)
    {
        return Runtime::resolve(HandleComponents::class)->snapshot($component, $context);
    }

    function fromSnapshot($snapshot)
    {
        return Runtime::resolve(HandleComponents::class)->fromSnapshot($snapshot);
    }

    function listen($eventName, $callback) {
        return on($eventName, $callback);
    }

    function current()
    {
        $stack = Runtime::resolve(HandleComponents::class)::$componentStack;

        return $stack === [] ? null : $stack[array_key_last($stack)];
    }

    function findSynth($keyOrTarget, $component)
    {
        return Runtime::resolve(HandleSynths::class)->find($keyOrTarget, $component);
    }

    function update($snapshot, $diff, $calls)
    {
        Runtime::boot();

        return Runtime::resolve(HandleComponents::class)->update($snapshot, $diff, $calls);
    }

    function updateProperty($component, $path, $value)
    {
        $dummyContext = new ComponentContext($component, false);

        $updatedHook = Runtime::resolve(HandleComponents::class)->updateProperty($component, $path, $value, $dummyContext);

        $updatedHook();
    }

    function isLivewireRequest()
    {
        return Runtime::resolve(HandleRequests::class)->isLivewireRequest();
    }

    function componentHasBeenRendered()
    {
        return SupportAutoInjectedAssets::$hasRenderedAComponentThisRequest;
    }

    function forceAssetInjection()
    {
        SupportAutoInjectedAssets::$forceAssetInjection = true;
    }

    function setUpdateRoute($callback)
    {
        return Runtime::resolve(HandleRequests::class)->setUpdateRoute($callback);
    }

    function getUriPrefix()
    {
        return Runtime::resolve(HandleRequests::class)->getUriPrefix();
    }

    function getUpdateUri()
    {
        return Runtime::resolve(HandleRequests::class)->getUpdateUri();
    }

    function setScriptRoute($callback)
    {
        return Runtime::resolve(FrontendAssets::class)->setScriptRoute($callback);
    }

    function useScriptTagAttributes($attributes)
    {
        return Runtime::resolve(FrontendAssets::class)->useScriptTagAttributes($attributes);
    }

    protected $queryParamsForTesting = [];

    protected $cookiesForTesting = [];

    protected $headersForTesting = [];

    function withUrlParams($params)
    {
        return $this->withQueryParams($params);
    }

    function withQueryParams($params)
    {
        $this->queryParamsForTesting = $params;

        return $this;
    }

    function withCookie($name, $value)
    {
        $this->cookiesForTesting[$name] = $value;

        return $this;
    }

    function withCookies($cookies)
    {
        $this->cookiesForTesting = array_merge($this->cookiesForTesting, $cookies);

        return $this;
    }

    function withHeaders($headers)
    {
        $this->headersForTesting = array_merge($this->headersForTesting, $headers);

        return $this;
    }

    function withoutLazyLoading()
    {
        SupportLazyLoading::disableWhileTesting();

        return $this;
    }

    /**
     * @template TComponent of \Webkernel\Liveview\Component
     *
     * @param class-string<TComponent>|TComponent|string|array<array-key, \Webkernel\Liveview\Component> $name
     * @param array $params
     *
     * @return Testable<TComponent>
     */
    function test($name, $params = [])
    {
        return Testable::create(
            $name,
            $params,
            $this->queryParamsForTesting,
            $this->cookiesForTesting,
            $this->headersForTesting,
        );
    }

    function actingAs(\Webkernel\Accounts\Contracts\Authenticatable $user, $driver = null)
    {
         Testable::actingAs($user, $driver);

         return $this;
    }

    function addPersistentMiddleware($middleware)
    {
        Runtime::resolve(PersistentMiddleware::class)->addPersistentMiddleware($middleware);
    }

    function setPersistentMiddleware($middleware)
    {
        Runtime::resolve(PersistentMiddleware::class)->setPersistentMiddleware($middleware);
    }

    function getPersistentMiddleware()
    {
        return Runtime::resolve(PersistentMiddleware::class)->getPersistentMiddleware();
    }

    function zap()
    {
        throw new \LogicException('Livewire Zap debugging is not available in the Webkernel runtime.');
    }

    function flushState()
    {
        trigger('flush-state');
    }

    function originalUrl()
    {
        if ($this->isLivewireRequest()) {
            return url()->to($this->originalPath());
        }

        return url()->current();
    }

    function originalPath()
    {
        if ($this->isLivewireRequest()) {
            $snapshot = json_decode(request('components.0.snapshot'), true);

            return data_get($snapshot, 'memo.path', 'POST');
        }

        return request()->path();
    }

    function originalMethod()
    {
        if ($this->isLivewireRequest()) {
            $snapshot = json_decode(request('components.0.snapshot'), true);

            return data_get($snapshot, 'memo.method', 'POST');
        }

        return request()->method();
    }

    function isCspSafe()
    {
        return config('liveview.csp_safe', false);
    }
}
