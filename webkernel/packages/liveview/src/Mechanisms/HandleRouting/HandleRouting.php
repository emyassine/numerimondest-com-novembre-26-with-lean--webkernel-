<?php

namespace Webkernel\Liveview\Mechanisms\HandleRouting;

use Webkernel\Route\Route;
use Webkernel\Liveview\Features\SupportLazyLoading\SupportLazyLoading;
use Webkernel\Liveview\Mechanisms\Mechanism;
use Webkernel\Liveview\Webkernel\Runtime;

class HandleRouting extends Mechanism
{
    public function register()
    {
        parent::register();

        Route::macro('liveview', function ($uri, $component) {
            if (is_object($component)) {
                Runtime::resolve('liveview')->addComponent($component);
            }

            return Route::get($uri, LivewirePageController::class)
                ->set_attribute('liveview_component', $component);
        });

        SupportLazyLoading::registerRouteMacro();
    }
}
