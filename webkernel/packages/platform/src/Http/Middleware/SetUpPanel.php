<?php declare(strict_types=1);

namespace Webkernel\Platform\Http\Middleware;

use Webkernel\Platform\PlatformManager;
use Webkernel\Platform\Panel;
use Webkernel\Platform\PanelProvider;
use Webkernel\Platform\PanelRegistry;
use Webkernel\Platform\Support\Assets\AssetManager;
use Webkernel\Platform\Support\Webkernel\AttributeBagMacros;
use Webkernel\Platform\Webkernel\Assets;
use Webkernel\Liveview\Features\SupportPageComponents\SupportPageComponents;
use RuntimeException;
use Webkernel\Container\Container;
use Webkernel\Route\Middleware\ParameterizedRouteMiddleware;
use Webkernel\Route\Request;

class SetUpPanel implements ParameterizedRouteMiddleware
{
    public function handle(Request $request, callable $next, string ...$parameters): mixed
    {
        $profile_request = (getenv('WEBKERNEL_PROFILE_REQUEST') ?: '') === '1';
        $phase_started_at = hrtime(true);
        $timings = [];
        $mark_phase = static function (string $name) use (&$phase_started_at, &$timings, $profile_request): void {
            if (! $profile_request) {
                return;
            }

            $now = hrtime(true);
            $timings[$name] = ($now - $phase_started_at) / 1e6;
            $phase_started_at = $now;
        };

        $id = $parameters[0] ?? '';
        $provider = $parameters[1] ?? null;
        if ($id === '' || count($parameters) > 2) {
            throw new RuntimeException('A compiled panel middleware declaration requires its panel ID.');
        }
        AttributeBagMacros::register();

        $container = Container::getInstance();
        Assets::register($container->make(AssetManager::class));
        $mark_phase('panel_assets');
        if (! $container->bound(PanelRegistry::class)) { $container->scoped(PanelRegistry::class); }
        if (! $container->bound(PlatformManager::class)) { $container->scoped(PlatformManager::class); }
        $registry = $container->make(PanelRegistry::class);
        $panel = $registry->get($id);
        if ($panel === null) {
            if ($provider === null || ! is_subclass_of($provider, PanelProvider::class)) {
                throw new RuntimeException('The compiled panel definition provider is unavailable.');
            }
            $panel = $container->make($provider)->panel(Panel::make())->definitionProvider($provider);
            if ($panel->getId() !== $id) { throw new RuntimeException('The panel definition no longer matches the compiled route.'); }
            $registry->panels[$id] = $panel;
        }
        $mark_phase('panel_definition');
        $manager = $container->make(PlatformManager::class);
        $manager->setCurrentPanel($panel);
        $panel->register();
        SupportPageComponents::registerLayoutViewMacros();
        $manager->bootCurrentPanel();
        $mark_phase('panel_boot');

        try {
            return $next();
        } finally {
            if ($profile_request) {
                $timings['panel_downstream'] = (hrtime(true) - $phase_started_at) / 1e6;
                $existing = $GLOBALS['webkernel_request_phase_timings'] ?? [];
                $GLOBALS['webkernel_request_phase_timings'] = is_array($existing)
                    ? [...$existing, ...$timings]
                    : $timings;
            }
        }
    }
}
