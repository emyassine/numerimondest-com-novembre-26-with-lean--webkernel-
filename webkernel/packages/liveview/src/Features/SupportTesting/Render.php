<?php

namespace Webkernel\Liveview\Features\SupportTesting;

use function Webkernel\Liveview\on;

abstract class Render
{
    protected function extractComponentAndTemplateView($callback)
    {
        $instance = null;
        $extractedView = null;

        $offA = on('dehydrate', function ($component) use (&$instance) {
            $instance = $component;
        });

        $offB = on('render', function ($component, $view) use (&$extractedView) {
            return function () use ($view, &$extractedView) {
                $extractedView = $view;
            };
        });

        $result = $callback();

        $offA(); $offB();

        return [$result, $instance, $extractedView];
    }
}
