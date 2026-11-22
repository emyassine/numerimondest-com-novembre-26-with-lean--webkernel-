<?php

namespace Webkernel\Liveview\Features\SupportPageComponents;

trait HandlesPageComponents
{
    function __invoke()
    {
        // Here's we're hooking into the "__invoke" method being called on a component.
        // This way, users can pass Livewire components into Routes as if they were
        // simple invokable controllers. Ex: Route::get('...', SomeLivewireComponent::class);
        $html = null;

        $layoutConfig = SupportPageComponents::interceptTheRenderOfTheComponentAndRetreiveTheLayoutConfiguration(function () use (&$html) {
            $params = SupportPageComponents::gatherMountMethodParamsFromRouteParameters($this);

            $name = $this->getName() !== null ? $this->getName() : $this::class;

            $html = \Webkernel\Liveview\Webkernel\Runtime::resolve(\Webkernel\Liveview\LivewireManager::class)->mount($name, $params);
        });

        $layoutConfig = $layoutConfig ?: new PageComponentConfig;

        $response = new \Symfony\Component\HttpFoundation\Response(
            SupportPageComponents::renderContentsIntoLayout($html, $layoutConfig),
        );

        if (is_callable($layoutConfig->response)) {
            call_user_func($layoutConfig->response, $response);
        }

        http_response_code($response->getStatusCode());
        foreach ($response->headers->allPreserveCaseWithoutCookies() as $name => $values) {
            header($name.': '.implode(', ', $values), false);
        }

        return (string) $response->getContent();
    }
}
