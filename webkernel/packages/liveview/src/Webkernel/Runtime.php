<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Liveview\Webkernel;

use Webkernel\Liveview\Mechanisms\HandleComponents\HandleComponents;
use Webkernel\Liveview\Mechanisms\HandleRequests\HandleRequests;
use Webkernel\Liveview\Mechanisms\HandleRouting\HandleRouting;
use Webkernel\Liveview\Compiler\CacheManager;
use Webkernel\Liveview\Compiler\Compiler;
use Webkernel\Liveview\EventBus;
use Webkernel\Liveview\Factory\Factory;
use Webkernel\Liveview\Finder\Finder;
use Webkernel\Liveview\ComponentHookRegistry;
use Webkernel\Liveview\Features\SupportLifecycleHooks\SupportLifecycleHooks;
use Webkernel\Liveview\Features\SupportIslands\SupportIslands;
use Webkernel\Liveview\Features\SupportScriptsAndAssets\SupportScriptsAndAssets;
use Webkernel\Liveview\Features\SupportLazyLoading\SupportLazyLoading;
use Webkernel\Liveview\Features\SupportCompiledWireKeys\SupportCompiledWireKeys;
use Webkernel\Liveview\Features\SupportPageComponents\SupportPageComponents;
use Webkernel\Container\Container;
use Webkernel\Config\Config;

/** Small native Webkernel container bridge for Livewire's rendering entrypoints. */
final class Runtime
{
    private static ?\Webkernel\Route\Route $registeredRouter = null;
    private static ?Container $registeredContainer = null;
    private static ?Container $bootedContainer = null;

    public static function resolve(string $abstract): mixed
    {
        $container = Container::getInstance();
        self::registerCoreServices($container);

        $abstract = match ($abstract) {
            'liveview' => \Webkernel\Liveview\LivewireManager::class,
            'liveview.finder' => Finder::class,
            'liveview.compiler' => Compiler::class,
            'liveview.factory' => Factory::class,
            default => $abstract,
        };

        return $container->make($abstract);
    }

    public static function mount(mixed $name, array $params = [], mixed $key = null, array $slots = []): mixed
    {
        self::bootRuntime();

        return self::resolve(HandleComponents::class)->mount($name, $params, $key, $slots);
    }

    /** Boot the minimum Livewire lifecycle needed to mount and render components. */
    public static function boot(): void
    {
        self::bootRuntime();
    }

    /** Boot the minimum Livewire lifecycle needed to mount and render components. */
    private static function bootRuntime(): void
    {
        $container = Container::getInstance();
        self::registerCoreServices($container);

        if (self::$bootedContainer === $container) {
            return;
        }

        $finder = self::resolve(Finder::class);
        foreach (Config::get('liveview.component_namespaces', []) as $namespace => $directory) {
            if (! is_string($namespace) || ! is_string($directory) || $directory === '') { continue; }
            $finder->addNamespace($namespace, viewPath: $directory);
            \Webkernel\View\View::addNamespace($namespace, $directory, components: true);
        }
        foreach (Config::get('liveview.component_locations', []) as $directory) {
            if (! is_string($directory) || $directory === '') { continue; }
            $finder->addLocation(viewPath: $directory);
            \Webkernel\View\View::addNamespace('global', $directory, components: true);
        }

        $synths = self::resolve(\Webkernel\Liveview\Mechanisms\HandleSynths\HandleSynths::class);
        $components = self::resolve(HandleComponents::class);
        $synths->boot();
        $synths->registerSynth(WebkernelModelSynth::class);
        $components->boot();

        $frontendAssets = self::resolve(\Webkernel\Liveview\Mechanisms\FrontendAssets\FrontendAssets::class);
        $container->instance(\Webkernel\Liveview\Mechanisms\FrontendAssets\FrontendAssets::class, $frontendAssets);
        $frontendAssets->boot();

        $persistentMiddleware = self::resolve(\Webkernel\Liveview\Mechanisms\PersistentMiddleware\PersistentMiddleware::class);
        $persistentMiddleware->register();
        $persistentMiddleware->boot();

        $componentHooks = [
            \Webkernel\Liveview\Features\SupportWireModelingNestedComponents\SupportWireModelingNestedComponents::class,
            \Webkernel\Liveview\Features\SupportMultipleRootElementDetection\SupportMultipleRootElementDetection::class,
            \Webkernel\Liveview\Features\SupportNestedComponentListeners\SupportNestedComponentListeners::class,
            \Webkernel\Liveview\Features\SupportAutoInjectedAssets\SupportAutoInjectedAssets::class,
            \Webkernel\Liveview\Features\SupportComputed\SupportComputedProperties::class,
            \Webkernel\Liveview\Features\SupportNestingComponents\SupportNestingComponents::class,
            \Webkernel\Liveview\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::class,
            \Webkernel\Liveview\Features\SupportScriptsAndAssets\SupportScriptsAndAssets::class,
            \Webkernel\Liveview\Features\SupportPageComponents\SupportPageComponents::class,
            \Webkernel\Liveview\Features\SupportReactiveProps\SupportReactiveProps::class,
            \Webkernel\Liveview\Features\SupportReleaseTokens\SupportReleaseTokens::class,
            \Webkernel\Liveview\Features\SupportFileDownloads\SupportFileDownloads::class,
            \Webkernel\Liveview\Features\SupportJsEvaluation\SupportJsEvaluation::class,
            \Webkernel\Liveview\Features\SupportMagicActions\SupportMagicActions::class,
            \Webkernel\Liveview\Features\SupportQueryString\SupportQueryString::class,
            \Webkernel\Liveview\Features\SupportFileUploads\SupportFileUploads::class,
            \Webkernel\Liveview\Features\SupportTeleporting\SupportTeleporting::class,
            \Webkernel\Liveview\Features\SupportLazyLoading\SupportLazyLoading::class,
            \Webkernel\Liveview\Features\SupportFormObjects\SupportFormObjects::class,
            \Webkernel\Liveview\Features\SupportAttributes\SupportAttributes::class,
            \Webkernel\Liveview\Features\SupportPagination\SupportPagination::class,
            \Webkernel\Liveview\Features\SupportValidation\SupportValidation::class,
            \Webkernel\Liveview\Features\SupportWithMethod\SupportWithMethod::class,
            \Webkernel\Liveview\Features\SupportIsolating\SupportIsolating::class,
            \Webkernel\Liveview\Features\SupportRedirects\SupportRedirects::class,
            \Webkernel\Liveview\Features\SupportTransitions\SupportTransitions::class,
            \Webkernel\Liveview\Features\SupportStreaming\SupportStreaming::class,
            \Webkernel\Liveview\Features\SupportJsModules\SupportJsModules::class,
            \Webkernel\Liveview\Features\SupportCssModules\SupportCssModules::class,
            \Webkernel\Liveview\Features\SupportNavigate\SupportNavigate::class,
            \Webkernel\Liveview\Features\SupportEntangle\SupportEntangle::class,
            \Webkernel\Liveview\Features\SupportWireRef\SupportWireRef::class,
            \Webkernel\Liveview\Features\SupportLocales\SupportLocales::class,
            \Webkernel\Liveview\Features\SupportIslands\SupportIslands::class,
            \Webkernel\Liveview\Features\SupportModels\SupportModels::class,
            \Webkernel\Liveview\Features\SupportEvents\SupportEvents::class,
            \Webkernel\Liveview\Features\SupportSlots\SupportSlots::class,
            \Webkernel\Liveview\Features\SupportJson\SupportJson::class,
            \Webkernel\Liveview\Features\SupportLifecycleHooks\SupportLifecycleHooks::class,
            \Webkernel\Liveview\Features\SupportWireables\SupportWireables::class,
        ];

        foreach ($componentHooks as $componentHook) {
            self::resolve(\Webkernel\Liveview\LivewireManager::class)->componentHook($componentHook);
        }

        ComponentHookRegistry::boot();

        self::$bootedContainer = $container;
    }

    /** Register Livewire route macros and its update endpoint with Webkernel. */
    public static function registerRoutes(): void
    {
        $router = \Webkernel\Route\Route::app();
        if (self::$registeredRouter === $router) {
            return;
        }

        self::bootRuntime();

        $routing = self::resolve(HandleRouting::class);
        $routing->register();

        $requests = self::resolve(HandleRequests::class);
        $requests->register();
        $requests->resetRouteRegistration();
        $requests->boot();

        self::$registeredRouter = $router;
    }

    private static function registerCoreServices(Container $container): void
    {
        if (self::$registeredContainer === $container) {
            return;
        }

        $container->alias(\Webkernel\Liveview\LivewireManager::class, 'liveview');
        $container->alias(Finder::class, 'liveview.finder');
        $container->alias(Compiler::class, 'liveview.compiler');
        $container->alias(Factory::class, 'liveview.factory');
        $container->singleton(Finder::class);
        $container->singleton(CacheManager::class, static fn (): CacheManager => new CacheManager(base_path('runtime/cache/liveview')));
        $container->singleton(Compiler::class, static fn (): Compiler => new Compiler(self::resolve(CacheManager::class)));
        $container->singleton(Factory::class);
        $container->singleton(EventBus::class);
        $container->singleton(\Webkernel\Liveview\Mechanisms\HandleSynths\HandleSynths::class);
        $container->singleton(HandleComponents::class);
        self::$registeredContainer = $container;
    }
}
