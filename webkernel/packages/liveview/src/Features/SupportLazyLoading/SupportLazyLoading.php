<?php

namespace Webkernel\Liveview\Features\SupportLazyLoading;

use Webkernel\Liveview\Features\SupportLifecycleHooks\SupportLifecycleHooks;
use Webkernel\Liveview\Mechanisms\HandleComponents\ComponentContext;
use Webkernel\Liveview\Mechanisms\HandleComponents\CorruptComponentPayloadException;
use Webkernel\Liveview\Mechanisms\HandleComponents\ViewContext;
use function Webkernel\Liveview\{ on, trigger, wrap };
use Webkernel\Route\Binding;
use Webkernel\Liveview\ComponentHook;
use Webkernel\Liveview\Drawer\Utils;
use Webkernel\Liveview\Component;
use Webkernel\View\View as WebkernelView;

class SupportLazyLoading extends ComponentHook
{
    protected const MOUNT_PARAMS_CONTAINER = '__mountParamsContainer';

    static $disableWhileTesting = false;

    static function disableWhileTesting()
    {
        static::$disableWhileTesting = true;
    }

    static function provide()
    {
        on('flush-state', function () {
            static::$disableWhileTesting = false;
        });
    }

    static function registerRouteMacro()
    {
        Binding::macro('lazy', function ($enabled = true) {
            $this->defaults['lazy'] = $enabled;

            return $this;
        });

        Binding::macro('defer', function ($enabled = true) {
            $this->defaults['defer'] = $enabled;

            return $this;
        });
    }

    public function mount($params)
    {
        $shouldBeLazy = false;
        $isDeferred = false;
        $isolate = true;

        if (isset($params['lazy']) && $params['lazy']) $shouldBeLazy = true;
        if (isset($params['lazy.bundle']) && $params['lazy.bundle']) $shouldBeLazy = true;
        if (isset($params['defer']) && $params['defer']) $shouldBeLazy = true;
        if (isset($params['defer.bundle']) && $params['defer.bundle']) $shouldBeLazy = true;

        if (isset($params['lazy']) && $params['lazy'] === 'on-load') $isDeferred = true;
        if (isset($params['lazy.bundle']) && $params['lazy.bundle'] === 'on-load') $isDeferred = true;
        if (isset($params['defer']) && $params['defer']) $isDeferred = true;
        if (isset($params['defer.bundle']) && $params['defer.bundle']) $isDeferred = true;

        if (isset($params['lazy.bundle']) && $params['lazy.bundle']) $isolate = false;
        if (isset($params['defer.bundle']) && $params['defer.bundle']) $isolate = false;

        $reflectionClass = new \ReflectionClass($this->component);
        $lazyAttribute = $reflectionClass->getAttributes(\Webkernel\Liveview\Attributes\Lazy::class)[0] ?? null;
        $deferAttribute = $reflectionClass->getAttributes(\Webkernel\Liveview\Attributes\Defer::class)[0] ?? null;

        // Apply attributes only if the corresponding param is not explicitly false...
        $lazyDisabled = isset($params['lazy']) && $params['lazy'] === false;
        $deferDisabled = isset($params['defer']) && $params['defer'] === false;

        if ($lazyAttribute && ! $lazyDisabled) $shouldBeLazy = true;
        if ($deferAttribute && ! $deferDisabled) $shouldBeLazy = true;
        if ($deferAttribute && ! $deferDisabled) $isDeferred = true;

        // If Livewire::withoutLazyLoading()...
        if (static::$disableWhileTesting) return;
        // If no lazy loading is included at all...
        if (! $shouldBeLazy) return;

        if ($lazyAttribute) {
            $attribute = $lazyAttribute->newInstance();

            if ($attribute->bundle !== null) $isolate = ! $attribute->bundle;
            if ($attribute->isolate !== null) $isolate = $attribute->isolate;
        }

        if ($deferAttribute) {
            $attribute = $deferAttribute->newInstance();

            if ($attribute->bundle !== null) $isolate = ! $attribute->bundle;
            if ($attribute->isolate !== null) $isolate = $attribute->isolate;
        }

        $this->component->skipMount();

        $this->storeSet('isLazyLoadMounting', true);
        $this->storeSet('isLazyIsolated', $isolate);

        $this->component->skipRender(
            $this->generatePlaceholderHtml($params, $isDeferred)
        );
    }

    public function hydrate($memo)
    {
        if (! isset($memo['lazyLoaded'])) return;

        if ($memo['lazyLoaded'] === true) {
            $this->storeSet('isLazyLoadAlreadyLoaded', true);

            return;
        }

        $this->component->skipHydrate();

        $this->storeSet('isLazyLoadHydrating', true);
    }

    function dehydrate($context)
    {
        if ($this->storeGet('isLazyLoadMounting') === true) {
            $context->addMemo('lazyLoaded', false);
            $context->addMemo('lazyIsolated', $this->storeGet('isLazyIsolated'));
        } elseif (
            $this->storeGet('isLazyLoadHydrating') === true
            || $this->storeGet('isLazyLoadAlreadyLoaded') === true
        ) {
            $context->addMemo('lazyLoaded', true);
        }
    }

    // Resume a deferred lazy load once the browser requests it.
    function call($method, $params, $returnEarly)
    {
        if ($method !== '__lazyLoad') return;

        // Ignore repeat calls after the component has already been loaded.
        if ($this->storeGet('isLazyLoadAlreadyLoaded') === true) {
            $returnEarly();

            return;
        }

        // Only applies while a lazy load is being resumed.
        if ($this->storeGet('isLazyLoadHydrating') !== true) return;

        [ $encoded ] = $params;

        $mountParams = $this->resurrectMountParams($encoded);

        $this->callMountLifecycleMethod($mountParams);

        $returnEarly();
    }

    // Render the placeholder and capture the mount params for the resume request.
    public function generatePlaceholderHtml($params, $isDeferred = false)
    {
        $this->registerContainerComponent();

        $container = app('liveview')->new(static::MOUNT_PARAMS_CONTAINER);

        $container->forMount = array_diff_key($params, array_flip(['lazy', 'defer']));

        // Remember which component these params belong to.
        $container->forComponent = $this->component->getName();

        $context = new ComponentContext($container, mounting: true);

        trigger('dehydrate', $container, $context);

        $snapshot = app('liveview')->snapshot($container, $context);

        $encoded = base64_encode(json_encode($snapshot));

        $placeholder = $this->getPlaceholderView($this->component, $params);

        $finish = trigger('render.placeholder', $this->component, $placeholder, $params);

        $viewContext = new ViewContext;

        $html = $placeholder->render();

        $html = Utils::insertAttributesIntoHtmlRoot($html, [
            ($isDeferred ? 'x-init' : 'x-intersect') => '$wire.__lazyLoad(\''.$encoded.'\')',
        ]);

        $replaceHtml = function ($newHtml) use (&$html) {
            $html = $newHtml;
        };

        $html = $finish($html, $replaceHtml, $viewContext);

        return $html;
    }

    protected function getPlaceholderView($component, $params)
    {
        // @todo: This is a hack. Fix this so it uses a deterministically generated name...
        $name = (string) str($this->component->getName())->afterLast('.');
        $compiledPlaceholder = "livewire-compiled::{$name}_placeholder";

        $globalPlaceholder = config('liveview.component_placeholder');

        if (WebkernelView::exists($compiledPlaceholder)) {
            $placeholderHtml = $compiledPlaceholder;
        } else if ($globalPlaceholder) {
            $placeholderHtml = WebkernelView::make($globalPlaceholder)->render();
        } else {
            $placeholderHtml = WebkernelView::html('<div></div>');
        }

        $viewOrString = wrap($component)->withFallback($placeholderHtml)->placeholder($params);

        $properties = Utils::getPublicPropertiesDefinedOnSubclass($component);

        $view = Utils::generateView($viewOrString, $properties);

        return $view;
    }

    // Rebuild the captured mount params from the container snapshot.
    function resurrectMountParams($encoded)
    {
        $snapshot = json_decode(base64_decode($encoded), associative: true);

        $this->registerContainerComponent();

        [ $container ] = app('liveview')->fromSnapshot($snapshot);

        if ($container->forComponent !== $this->component->getName()) {
            throw new CorruptComponentPayloadException;
        }

        return $container->forMount;
    }

    function callMountLifecycleMethod($params)
    {
        $hook = new SupportLifecycleHooks;

        $hook->setComponent($this->component);

        $hook->mount($params);
    }

    // A throwaway component used to carry mount params across the round-trip.
    public function registerContainerComponent()
    {
        app('liveview')->component(static::MOUNT_PARAMS_CONTAINER, new class extends Component {
            public $forMount;
            public $forComponent;
        });
    }
}
