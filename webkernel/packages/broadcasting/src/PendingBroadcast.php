<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Broadcasting;

use \Webkernel\Events\Contracts\Dispatcher;

use function \Webkernel\Utilities\enum_value;

class PendingBroadcast
{
    /**
     * The event dispatcher implementation.
     *
     * @var \Webkernel\Events\Contracts\Dispatcher
     */
    protected $events;

    /**
     * The event instance.
     *
     * @var mixed
     */
    protected $event;

    /**
     * Create a new pending broadcast instance.
     *
     * @param  \Webkernel\Events\Contracts\Dispatcher  $events
     * @param  mixed  $event
     */
    public function __construct(Dispatcher $events, $event)
    {
        $this->event = $event;
        $this->events = $events;
    }

    /**
     * Broadcast the event using a specific broadcaster.
     *
     * @param  \UnitEnum|string|null  $connection
     * @return $this
     */
    public function via($connection = null)
    {
        if (method_exists($this->event, 'broadcastVia')) {
            $this->event->broadcastVia(enum_value($connection));
        }

        return $this;
    }

    /**
     * Broadcast the event to everyone except the current user.
     *
     * @return $this
     */
    public function toOthers()
    {
        if (method_exists($this->event, 'dontBroadcastToCurrentUser')) {
            $this->event->dontBroadcastToCurrentUser();
        }

        return $this;
    }

    /**
     * Handle the object's destruction.
     *
     * @return void
     */
    public function __destruct()
    {
        $this->events->dispatch($this->event);
    }
}
