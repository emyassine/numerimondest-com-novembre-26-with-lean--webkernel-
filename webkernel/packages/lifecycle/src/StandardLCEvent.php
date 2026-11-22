<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
namespace Webkernel\Lifecycle;

use Composer\Script\Event;
use LogicException;

/**
 * Base for package lifecycle callbacks that handle Composer script events.
 */
abstract class StandardLCEvent
{
    private ?Event $composer_event = null;

    /**
     * Stores the Composer event and runs the listener implementation.
     * @param $event Event Composer script event received by this listener.
     * @throws \Throwable If the listener implementation fails.
     */
    final public function __invoke(Event $event): void
    {
        $this->composer_event = $event;
        $this->run();
    }

    /**
     * Returns the event active during the current listener invocation.
     * @throws LogicException When called outside a listener invocation.
     */
    final protected function composerEvent(): Event
    {
        return $this->composer_event
            ?? throw new LogicException('The Composer event is only available while the listener is running.');
    }

    /** Runs the listener implementation; subclasses provide the callback body. @throws \Throwable */
    abstract protected function run(): void;
}
