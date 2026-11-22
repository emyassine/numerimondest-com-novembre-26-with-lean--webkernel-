<?php

namespace Webkernel\Liveview\Features\SupportEvents;

use function Webkernel\Liveview\store;

trait HandlesEvents
{
    protected $listeners = [];

    protected function getListeners() {
        return $this->listeners;
    }

    public function dispatch($event, ...$params)
    {
        $event = new Event($event, $params);

        store($this)->push('dispatched', $event);

        return $event;
    }
}
