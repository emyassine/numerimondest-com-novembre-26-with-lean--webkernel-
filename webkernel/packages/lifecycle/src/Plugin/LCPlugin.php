<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
namespace Webkernel\Lifecycle\Plugin;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Webkernel\Lifecycle\Event\LCEventTarget;
use Webkernel\Lifecycle\Plugin\Activation\LCPluginActivator;
use Webkernel\Lifecycle\Plugin\Event\LCPluginScriptEventHandler;
use Webkernel\Lifecycle\Plugin\Event\LCPackageOperationEventHandler;

/**
 * Webkernel Composer plugin entry-point.
 *
 * 1. Registers the custom installer for webkernel-* package types.
 * 2. Subscribes to all Composer script events.
 * 3. Dispatches extra.webkernel.lifecycle.events.{event} callables across packages.
 */
final class LCPlugin implements PluginInterface, EventSubscriberInterface
{
    private readonly LCPluginScriptEventHandler $script_event_handler;

    public function __construct()
    {
        $this->script_event_handler = new LCPluginScriptEventHandler();
    }

    /**
     * Returns the Composer script and package events handled by this plugin.
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        $events = [];
        foreach (LCEventTarget::cases() as $hook) {
            $events[$hook->value] = 'onScriptEvent';
        }

        foreach ([
            'pre-package-install',
            'post-package-install',
            'pre-package-update',
            'post-package-update',
            'pre-package-uninstall',
            'post-package-uninstall',
        ] as $package_event) {
            $events[$package_event] = 'onPackageEvent';
        }

        return $events;
    }

    /**
     * Enables autoloader optimization and registers the Webkernel package installer.
     * @param $composer Composer Composer instance being configured.
     * @param $io IOInterface Composer console IO.
     * @return void
     */
    public function activate(Composer $composer, IOInterface $io): void
    {
        (new LCPluginActivator())->activate($composer, $io);
    }

    /**
     * Releases plugin state when Composer deactivates it.
     * @param $composer Composer Composer instance.
     * @param $io IOInterface Composer console IO.
     * @return void
     */
    public function deactivate(Composer $composer, IOInterface $io): void {}

    /**
     * Removes plugin state when Composer uninstalls it.
     * @param $composer Composer Composer instance.
     * @param $io IOInterface Composer console IO.
     * @return void
     */
    public function uninstall(Composer $composer, IOInterface $io): void {}

    /**
     * Dispatches declarations for a Composer script event.
     * @param $event Event Composer script event to dispatch.
     * @return void
     * @throws \Throwable If declaration loading or a callback fails.
     */
    public function onScriptEvent(Event $event): void
    {
        $this->script_event_handler->handle($event);
    }

    /**
     * Reports installation paths for Webkernel package operations.
     * @param $event PackageEvent Composer package operation event.
     * @return void
     * @throws \Throwable If the package install path cannot be resolved.
     */
    public function onPackageEvent(PackageEvent $event): void
    {
        (new LCPackageOperationEventHandler())->handle($event);
    }
}
