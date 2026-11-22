<?php

namespace Webkernel\Liveview\Features\SupportPageComponents;

use function Webkernel\Liveview\{on, once};
use Webkernel\Liveview\ComponentHook;
use Webkernel\Route\Route;
use Webkernel\View\View as WebkernelView;
use Webkernel\View\ViewResult;

class SupportPageComponents extends ComponentHook
{
    static function provide()
    {
        static::registerLayoutViewMacros();
    }

    static function registerLayoutViewMacros()
    {
        ViewResult::macro('layoutData', function ($data = []): static {
            $this->layoutConfig ??= new PageComponentConfig;
            $this->layoutConfig->mergeParams($data);
            return $this;
        });
        ViewResult::macro('section', function ($section): static {
            $this->layoutConfig ??= new PageComponentConfig;
            $this->layoutConfig->type = 'extends';
            $this->layoutConfig->slotOrSection = $section;
            return $this;
        });
        ViewResult::macro('title', function ($title): static {
            $this->layoutConfig ??= new PageComponentConfig;
            $this->layoutConfig->mergeParams(['title' => $title]);
            return $this;
        });
        ViewResult::macro('slot', function ($slot): static {
            $this->layoutConfig ??= new PageComponentConfig;
            $this->layoutConfig->type = 'component';
            $this->layoutConfig->slotOrSection = $slot;
            return $this;
        });
        ViewResult::macro('extends', function ($view, $params = []): static {
            $this->layoutConfig ??= new PageComponentConfig;
            $this->layoutConfig->type = 'extends';
            $this->layoutConfig->slotOrSection = 'content';
            $this->layoutConfig->view = $view;
            $this->layoutConfig->mergeParams($params);
            return $this;
        });
        //> Webkernel macro signature is (view, params): the layout slot keeps its
        //> default name, matching Livewire's own `layout()` macro.
        ViewResult::macro('layout', function ($view, $params = []): static {
            $this->layoutConfig ??= new PageComponentConfig;
            $this->layoutConfig->type = 'component';
            $this->layoutConfig->slotOrSection = 'slot';
            $this->layoutConfig->view = $view;
            $this->layoutConfig->mergeParams($params);
            return $this;
        });
        ViewResult::macro('response', function (callable $callback): static {
            $this->layoutConfig ??= new PageComponentConfig;
            $this->layoutConfig->response = $callback;
            return $this;
        });
    }

    static function interceptTheRenderOfTheComponentAndRetreiveTheLayoutConfiguration($callback)
    {
        $layoutConfig = null;
        // Only run this handler once for the parent-most component. Otherwise child components
        // will run this handler too and override the configured layout...
        $handler = once(function ($target, $view, $data) use (&$layoutConfig) {
            $layoutAttr = $target->getAttributes()->whereInstanceOf(BaseLayout::class)->first();
            $titleAttr = $target->getAttributes()->whereInstanceOf(BaseTitle::class)->first();

            if ($layoutAttr) {
                $view->layout($layoutAttr->name, $layoutAttr->params);
            }

            if ($titleAttr) {
                $view->title($titleAttr->content);
            }

            $layoutConfig = $view instanceof ViewResult ? $view->layoutConfig : null;
            $layoutConfig ??= new PageComponentConfig;

        });

        $render = on('render', $handler);
        $renderPlaceholder = on('render.placeholder', $handler);

        try {
            $callback();
        } finally {
            $render();
            $renderPlaceholder();
        }

        return $layoutConfig;
    }

    static function gatherMountMethodParamsFromRouteParameters($component)
    {
        // This allows for route parameters like "slug" in /post/{slug},
        // to be passed into a Livewire component's mount method...
        $route = Route::current();

        if (! $route) return [];
        $params = $route['parameters'] ?? [];
        if (method_exists($component, 'mount')) {
            $params = Route::resolveParametersFor([$component, 'mount'], $params) ?? [];
        }

        // Merge lazy/defer route defaults so that ->lazy() and ->defer()
        // route macros work even when the component has no mount() method...
        foreach (['lazy', 'defer'] as $key) {
            $defaults = $route['attributes']['_defaults'] ?? [];
            if (array_key_exists($key, $defaults) && ! array_key_exists($key, $params)) {
                $params[$key] = $defaults[$key];
            }
        }

        return $params;
    }

    static function renderContentsIntoLayout($content, $layoutConfig)
    {
        try {
            if ($layoutConfig->type === 'component') {
                // A page layout is a view rendered with its parameters and content slot.
                // Rendering through a synthetic @component wrapper hides object-valued
                // parameters from Webkernel's component scope (e.g. Filament's Livewire page).
                $data = $layoutConfig->params;
                $data[$layoutConfig->slotOrSection] = new \Webkernel\View\HtmlString((string) $content);

                return WebkernelView::resolve($layoutConfig->view, $data)->render();
            } else {
                return WebkernelView::renderWithSection(
                    $layoutConfig->view,
                    $layoutConfig->slotOrSection,
                    (string) $content,
                    $layoutConfig->params,
                );
            }
        } catch (\Throwable $e) {
            $layout = $layoutConfig->view;

            if (! WebkernelView::exists($layout)) {
                throw new MissingLayoutException($layout);
            } else {
                throw $e;
            }
        }
    }

}
