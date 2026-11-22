<?php

namespace Webkernel\Liveview\Features\SupportLifecycleHooks;

use function Webkernel\Liveview\wrap;
use function Webkernel\Liveview\on;
use Webkernel\Liveview\ComponentHook;

class SupportLifecycleHooks extends ComponentHook
{
    // Performance optimization: Cache trait lookups per component class...
    protected static $traitCache = [];

    // Performance optimization: Cache method existence checks per component class...
    protected static $methodCache = [];

    public static function provide()
    {
        on('flush-state', function () {
            static::$traitCache = [];
            static::$methodCache = [];
        });
    }

    public function mount($params)
    {
        if ($this->storeHas('skipMount')) { return; }

        $this->callHook('boot');
        $this->callTraitHook('boot');

        $this->callTraitHook('initialize');

        $this->callHook('mount', $params);
        $this->callTraitHook('mount', $params);

        $this->callHook('booted');
        $this->callTraitHook('booted');
    }

    public function hydrate()
    {
        if ($this->storeHas('skipHydrate')) { return; }

        $this->callHook('boot');
        $this->callTraitHook('boot');

        $this->callTraitHook('initialize');

        $this->callHook('hydrate');
        $this->callTraitHook('hydrate');

        // Call "hydrateXx" hooks for each property...
        foreach ($this->getProperties() as $property => $value) {
            $this->callHook('hydrate'.static::studly($property), [$value]);
        }

        $this->callHook('booted');
        $this->callTraitHook('booted');
    }

    public function update($propertyName, $fullPath, $newValue)
    {
        $name = $fullPath;

        $propertyName = explode('.', static::studly($name), 2)[0];
        $firstDot = strpos($name, '.');
        $lastDot = strrpos($name, '.');
        $keyAfterFirstDot = $firstDot === false ? null : substr($name, $firstDot + 1);
        $keyAfterLastDot = $lastDot === false ? null : substr($name, $lastDot + 1);

        $beforeMethod = 'updating'.$propertyName;
        $afterMethod = 'updated'.$propertyName;

        $beforeNestedMethod = $firstDot !== false
            ? 'updating'.static::studly(str_replace('.', '_', $name))
            : false;

        $afterNestedMethod = $firstDot !== false
            ? 'updated'.static::studly(str_replace('.', '_', $name))
            : false;

        $this->callHook('updating', [$fullPath, $newValue]);
        $this->callTraitHook('updating', [$fullPath, $newValue]);

        $this->callHook($beforeMethod, [$newValue, $keyAfterFirstDot]);

        $this->callHook($beforeNestedMethod, [$newValue, $keyAfterLastDot]);

        return function () use ($fullPath, $afterMethod, $afterNestedMethod, $keyAfterFirstDot, $keyAfterLastDot, $newValue) {
            $this->callHook('updated', [$fullPath, $newValue]);
            $this->callTraitHook('updated', [$fullPath, $newValue]);

            $this->callHook($afterMethod, [$newValue, $keyAfterFirstDot]);

            $this->callHook($afterNestedMethod, [$newValue, $keyAfterLastDot]);
        };
    }

    public function call($methodName, $params, $returnEarly, $metadata)
    {
        $protectedMethods = [
            'mount',
            'boot',
            'booted',
            'exception',
            'hydrate*',
            'dehydrate*',
            'updating*',
            'updated*',
            'rendering',
            'rendered',
            'scriptSrc',
        ];

        // Also block trait-suffixed lifecycle hooks (e.g. mountWithFileUploads, bootMyTrait)
        $class = get_class($this->component);

        if (! isset(static::$traitCache[$class])) {
            static::$traitCache[$class] = static::traitsRecursive($this->component);
        }

        foreach (static::$traitCache[$class] as $trait) {
            $traitBasename = static::classBasename($trait);
            $protectedMethods[] = 'mount'.$traitBasename;
            $protectedMethods[] = 'boot'.$traitBasename;
            $protectedMethods[] = 'booted'.$traitBasename;
        }

        foreach ($protectedMethods as $pattern) {
            if (fnmatch($pattern, $methodName)) {
                throw new DirectlyCallingLifecycleHooksNotAllowedException($methodName, $this->component->getName());
            }
        }

        $this->callTraitHook('call', ['methodName' => $methodName, 'params' => $params, 'returnEarly' => $returnEarly, 'metadata' => $metadata]);
    }

    public function exception($e, $stopPropagation)
    {
        $this->callHook('exception', ['e' => $e, 'stopPropagation' => $stopPropagation]);
        $this->callTraitHook('exception', ['e' => $e, 'stopPropagation' => $stopPropagation]);
    }

    public function render($view, $data)
    {
        $this->callHook('rendering', ['view' => $view, 'data' => $data]);
        $this->callTraitHook('rendering', ['view' => $view, 'data' => $data]);

        return function ($html) use ($view) {
            $this->callHook('rendered', ['view' => $view, 'html' => $html]);
            $this->callTraitHook('rendered', ['view' => $view, 'html' => $html]);
        };
    }

    public function dehydrate()
    {
        $this->callHook('dehydrate');
        $this->callTraitHook('dehydrate');

        // Call "dehydrateXx" hooks for each property...
        foreach ($this->getProperties() as $property => $value) {
            $this->callHook('dehydrate'.static::studly($property), [$value]);
        }
    }

    public function callHook($name, $params = [])
    {
        // Performance optimization: Cache method existence checks
        $class = get_class($this->component);
        $cacheKey = "{$class}::{$name}";

        if (!isset(static::$methodCache[$cacheKey])) {
            static::$methodCache[$cacheKey] = method_exists($this->component, $name);
        }

        if (static::$methodCache[$cacheKey]) {
            wrap($this->component)->__call($name, $params);
        }
    }

    function callTraitHook($name, $params = [])
    {
        // Performance optimization: Cache trait lookups per component class
        $class = get_class($this->component);

        if (!isset(static::$traitCache[$class])) {
            static::$traitCache[$class] = static::traitsRecursive($this->component);
        }

        foreach (static::$traitCache[$class] as $trait) {
            $method = $name.static::classBasename($trait);

            // Performance optimization: Cache method existence checks
            $cacheKey = "{$class}::{$method}";

            if (!isset(static::$methodCache[$cacheKey])) {
                static::$methodCache[$cacheKey] = method_exists($this->component, $method);
            }

            if (static::$methodCache[$cacheKey]) {
                // resolveMethodDependencies() can produce arrays with both
                // string and integer keys (e.g. ['postId' => '123', 0 => null]).
                // PHP forbids positional args after named args when spreading,
                // so strip the integer-keyed entries in that case. When all keys
                // are the same type (e.g. updating/updated hooks pass only
                // integer-keyed [$name, $value]), leave them as-is.
                $keys = array_keys($params);
                $hasStringKeys = array_filter($keys, 'is_string');
                $hasIntKeys = array_filter($keys, 'is_int');

                $paramsToSpread = ($hasStringKeys && $hasIntKeys)
                    ? array_filter($params, 'is_string', ARRAY_FILTER_USE_KEY)
                    : $params;

                wrap($this->component)->$method(...$paramsToSpread);
            }
        }
    }

    private static function studly(string $value): string
    {
        $value = preg_replace('/([a-z0-9])([A-Z])/', '$1 $2', $value) ?? $value;
        $value = preg_replace('/[^a-zA-Z0-9]+/', ' ', $value) ?? $value;

        return str_replace(' ', '', ucwords(strtolower($value)));
    }

    private static function classBasename(string $class): string
    {
        $position = strrpos($class, '\\');

        return $position === false ? $class : substr($class, $position + 1);
    }

    /** @return list<string> */
    private static function traitsRecursive(object $component): array
    {
        $traits = [];
        $collect = static function (string $trait) use (&$collect, &$traits): void {
            if (isset($traits[$trait])) {
                return;
            }
            $traits[$trait] = true;
            foreach (class_uses($trait) ?: [] as $nested) {
                $collect($nested);
            }
        };

        foreach ([get_class($component), ...array_values(class_parents($component) ?: [])] as $class) {
            foreach (class_uses($class) ?: [] as $trait) {
                $collect($trait);
            }
        }

        return array_keys($traits);
    }
}
