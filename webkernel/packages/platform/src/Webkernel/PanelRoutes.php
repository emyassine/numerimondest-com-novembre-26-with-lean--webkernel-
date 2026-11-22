<?php declare(strict_types=1);

namespace Webkernel\Platform\Webkernel;

use Webkernel\Platform\PlatformManager;
use Webkernel\Platform\Panel;
use Webkernel\Platform\PanelProvider;
use Webkernel\Platform\PanelRegistry;
use RuntimeException;
use Webkernel\Container\Container;
use Webkernel\Route\Route;

/** Builds panel declarations when Webkernel rebuilds its ordinary route collection. */
final class PanelRoutes
{
    private static ?Route $router = null;

    public static function registerProviders(?string $manifest = null): void
    {
        $router = Route::app();
        if (self::$router === $router) { return; }

        $container = Container::getInstance();
        if (! $container->bound(PanelRegistry::class)) { $container->scoped(PanelRegistry::class); }
        if (! $container->bound(PlatformManager::class)) { $container->scoped(PlatformManager::class); }
        $container->alias(PlatformManager::class, 'platform');
        $container->onScopeReset([PlatformManager::class, 'resetActivePanel']);
        $registry = $container->make(PanelRegistry::class);
        $manifest ??= base_path('runtime/cache/declarations/webkernel_panels.php');
        if (! is_file($manifest)) { self::$router = $router; return; }
        $providers = require $manifest;
        $owners_path = dirname($manifest).'/webkernel_panel_owners.php';
        $owners = is_file($owners_path) ? require $owners_path : [];
        if (! is_array($providers) || ! is_array($owners)) {
            throw new RuntimeException('Invalid compiled panel declaration manifest.');
        }
        $panels = [];
        $locations = [];
        foreach ($providers as $provider) {
            if (! is_string($provider) || ! is_subclass_of($provider, PanelProvider::class)) {
                throw new RuntimeException('Panel declarations must extend Webkernel\Platform\\PanelProvider.');
            }
            $panel = $container->make($provider)->panel(Panel::make())->definitionProvider($provider);
            $id = $panel->getId();
            $path = trim($panel->getPath(), '/');
            $scope = $panel->getScope();
            $owner = $owners[$provider] ?? null;
            if (! is_array($owner)
                || ($scope === 'module' && ($owner['type'] ?? null) !== 'webkernel-business-module')
                || ($scope === 'platform' && ($owner['owner'] ?? null) !== ($owner['root'] ?? null) && ($owner['type'] ?? null) !== 'library')) {
                throw new RuntimeException('The package cannot declare this panel scope: '.$provider);
            }
            if (preg_match('/\A[a-z0-9][a-z0-9_-]*\z/', $id) !== 1 || isset($panels[$id])
                || preg_match('/[\x00-\x20?#]|(?:^|\/)\.\.(?:\/|$)/', $path)) {
                throw new RuntimeException('Invalid or duplicate panel ID/path: '.$provider);
            }
            $domains = $panel->getDomains() ?: ['*'];
            foreach ($locations as [$previous_path, $previous_domains]) {
                if ($path === $previous_path && (in_array('*', $domains, true) || in_array('*', $previous_domains, true) || array_intersect($domains, $previous_domains))) {
                    throw new RuntimeException('Panel routes overlap at path ['.$path.'].');
                }
            }
            $locations[] = [$path, $domains];
            $panels[$id] = $panel;
        }
        $registry->panels = $panels;
        $registry->defaultPanel = null;
        self::$router = $router;
    }
}
