<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
namespace Webkernel\Lifecycle;

use Composer\Script\Event;
use LogicException;
use Webkernel\Lifecycle\Contracts\LifecycleActionContract;

/**
 * Base for lifecycle actions with class-derived keys and display names.
 */
abstract class StandardLCAction implements LifecycleActionContract
{
    private ?Event $composer_event = null;

    /**
     * Returns the default kebab-case key derived from the action class name.
     * @return string Stable action identifier.
     */
    public function key(): string
    {
        $class_name = self::actionClassName(static::class);
        $key        = preg_replace('/(?<!^)[A-Z]/', '-$0', $class_name);

        return strtolower($key ?? $class_name);
    }

    /**
     * Returns the default human-readable name derived from the action class name.
     * @return string Action label for Composer output.
     */
    public function name(): string
    {
        $class_name = self::actionClassName(static::class);

        return preg_replace('/(?<!^)[A-Z]/', ' $0', $class_name) ?? $class_name;
    }

    /**
     * Runs this action with the current Composer script event.
     * @param $event Event Composer script event passed to this action.
     * @throws \Throwable If the action implementation fails.
     */
    final public function handle(Event $event): void
    {
        $this->composer_event = $event;
        $this->run();
    }

    /**
     * Returns the event active during the current action invocation.
     * @throws LogicException When called outside an action invocation.
     */
    final protected function composerEvent(): Event
    {
        return $this->composer_event
            ?? throw new LogicException('The Composer event is only available while the action is running.');
    }

    /** Runs the action implementation; subclasses provide the action body. @throws \Throwable */
    abstract protected function run(): void;

    /** Removes the namespace and optional Action suffix from a class name. */
    private static function actionClassName(string $class_name): string
    {
        $separator = strrpos($class_name, '\\');
        $short_name = $separator === false
            ? $class_name
            : substr($class_name, $separator + 1);

        return preg_replace('/Action$/', '', $short_name) ?? $short_name;
    }
}
