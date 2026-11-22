<?php

namespace Webkernel\Liveview\Mechanisms\HandleRouting;

use Webkernel\Liveview\Webkernel\Runtime;
use Webkernel\Container\Container;
use Webkernel\Route\Route;

class LivewirePageController
{
    public function __invoke()
    {
        $route = Route::current();
        $component = $route['attributes']['liveview_component'] ?? null;

        if ($component === null) {
            throw new \RuntimeException('The current Webkernel route has no Livewire component attribute.');
        }

        $instance = is_object($component)
            ? $component
            : Runtime::resolve('liveview')->new($component);

        return Container::getInstance()->call([$instance, '__invoke'], $route['parameters']);
    }
}
