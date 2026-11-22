<?php

namespace Webkernel\Liveview;

use Closure;

abstract class ComponentHook
{
    protected $component;

    function setComponent($component)
    {
        $this->component = $component;
    }

    function callBoot(...$params) {
        if (method_exists($this, 'boot')) $this->boot(...$params);
    }

    function callMount(...$params) {
        if (method_exists($this, 'mount')) $this->mount(...$params);
    }

    function callHydrate(...$params) {
        if (method_exists($this, 'hydrate')) $this->hydrate(...$params);
    }

    function callUpdate($propertyName, $fullPath, $newValue)
    {
        return method_exists($this, 'update')
            ? $this->finishHook($this->update($propertyName, $fullPath, $newValue))
            : null;
    }

    function callCall($method, $params, $returnEarly, $metadata, $componentContext)
    {
        return method_exists($this, 'call')
            ? $this->finishHook($this->call($method, $params, $returnEarly, $metadata, $componentContext))
            : null;
    }

    function callRender(...$params)
    {
        return method_exists($this, 'render')
            ? $this->finishHook($this->render(...$params))
            : null;
    }

    function callRenderIsland(...$params)
    {
        return method_exists($this, 'renderIsland')
            ? $this->finishHook($this->renderIsland(...$params))
            : null;
    }

    function callRenderPlaceholder(...$params)
    {
        return method_exists($this, 'renderPlaceholder')
            ? $this->finishHook($this->renderPlaceholder(...$params))
            : null;
    }

    /** Preserve callback scope and isolate reference arguments until completion. */
    private function finishHook(mixed $callback): ?Closure
    {
        if ($callback === null) {
            return null;
        }

        return function (...$params) use ($callback): void {
            if (is_callable($callback)) {
                $callback(...$params);
            }
        };
    }

    function callDehydrate(...$params) {
        if (method_exists($this, 'dehydrate')) $this->dehydrate(...$params);
    }

    function callDestroy(...$params) {
        if (method_exists($this, 'destroy')) $this->destroy(...$params);
    }

    function callException(...$params) {
        if (method_exists($this, 'exception')) $this->exception(...$params);
    }

    function getProperties()
    {
        return $this->component->all();
    }

    function getProperty($name)
    {
        return data_get($this->getProperties(), $name);
    }

    function storeSet($key, $value)
    {
        store($this->component)->set($key, $value);
    }

    function storePush($key, $value, $iKey = null)
    {
        store($this->component)->push($key, $value, $iKey);
    }

    function storeGet($key, $default = null)
    {
        return store($this->component)->get($key, $default);
    }

    function storeFind($key, $iKey = null, $default = null)
    {
        return store($this->component)->find($key, $iKey, $default);
    }

    function storeHas($key)
    {
        return store($this->component)->has($key);
    }
}
