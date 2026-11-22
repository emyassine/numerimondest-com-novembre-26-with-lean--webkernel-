<?php

namespace Webkernel\Liveview;

use Webkernel\Container\Facade;

/**
 * @method static void component($name, $class = null)
 * @method static void directive($name, $callback)
 * @method static \Webkernel\Liveview\Component new($name, $id = null)
 * @method static string mount($name, $params = [], $key = null)
 * @method static array snapshot($component)
 * @method static void fromSnapshot($snapshot)
 * @method static void listen($name, $callback)
 * @method static array update($snapshot, $diff, $calls)
 * @method static bool isLivewireRequest()
 * @method static void setUpdateRoute($callback)
 * @method static string getUpdateUri()
 * @method static void setScriptRoute($callback)
 * @method static void useScriptTagAttributes($attributes)
 * @method static \Webkernel\Liveview\LivewireManager withUrlParams($params)
 * @method static \Webkernel\Liveview\LivewireManager withQueryParams($params)
 * @method static \Webkernel\Liveview\Features\SupportTesting\Testable test($name, $params = [])
 * @method static \Webkernel\Liveview\LivewireManager actingAs($user, $driver = null)
 * @method static void addPersistentMiddleware($middleware)
 * @method static void setPersistentMiddleware($middleware)
 * @method static array getPersistentMiddleware()
 * @method static void flushState()
 * @method static string originalUrl()
 * @method static string originalPath()
 * @method static string originalMethod()
 * @method static \Webkernel\Liveview\LivewireManager withoutLazyLoading()
 *
 * @see \Webkernel\Liveview\LivewireManager
 */
class Livewire extends Facade
{
    public static function getFacadeAccessor()
    {
        return \Webkernel\Liveview\LivewireManager::class;
    }
}
