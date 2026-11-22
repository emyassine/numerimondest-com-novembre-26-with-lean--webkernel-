<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Accounts;

use Closure;
use \Webkernel\Accounts\Contracts\Factory as FactoryContract;
use \Webkernel\Config\Config;
use \Webkernel\Container\Container;
use \Webkernel\HttpFoundation\Request;
use \Webkernel\Utilities\RebindsCallbacksToSelf;
use InvalidArgumentException;
use ReflectionException;
use RuntimeException;

use function \Webkernel\Utilities\enum_value;

/**
 * @mixin \Webkernel\Accounts\Contracts\Guard
 * @mixin \Webkernel\Accounts\Contracts\StatefulGuard
 */
class AuthManager implements FactoryContract
{
    use CreatesUserProviders, RebindsCallbacksToSelf;

    /**
     * The application instance.
     *
     * @var \Webkernel\Container\Container
     */
    protected $app;

    /**
     * The registered custom driver creators.
     *
     * @var array
     */
    protected $customCreators = [];

    /**
     * The array of created "drivers".
     *
     * @var array
     */
    protected $guards = [];

    /**
     * The user resolver shared by various services.
     *
     * Determines the default user for Gate, Request, and the Authenticatable contract.
     *
     * @var \Closure
     */
    protected $userResolver;

    /**
     * Create a new Auth manager instance.
     *
     * @param  \Webkernel\Container\Container  $app
     */
    public function __construct(Container $app)
    {
        $this->app = $app;

        $this->userResolver = fn ($guard = null) => $this->guard($guard)->user();
    }

    /**
     * Attempt to get the guard from the local cache.
     *
     * @param  \UnitEnum|string|null  $name
     * @return \Webkernel\Accounts\Contracts\Guard|\Webkernel\Accounts\Contracts\StatefulGuard
     */
    public function guard($name = null)
    {
        $name = enum_value($name) ?: $this->getDefaultDriver();

        return $this->guards[$name] ??= $this->resolve($name);
    }

    /**
     * Resolve the given guard.
     *
     * @param  string  $name
     * @return \Webkernel\Accounts\Contracts\Guard|\Webkernel\Accounts\Contracts\StatefulGuard
     *
     * @throws \InvalidArgumentException
     */
    protected function resolve($name)
    {
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Auth guard [{$name}] is not defined.");
        }

        if (isset($this->customCreators[$config['driver']])) {
            return $this->callCustomCreator($name, $config);
        }

        $driverMethod = 'create'.ucfirst($config['driver']).'Driver';

        if (method_exists($this, $driverMethod)) {
            return $this->{$driverMethod}($name, $config);
        }

        throw new InvalidArgumentException(
            "Auth driver [{$config['driver']}] for guard [{$name}] is not defined."
        );
    }

    /**
     * Call a custom driver creator.
     *
     * @param  string  $name
     * @param  array  $config
     * @return mixed
     */
    protected function callCustomCreator($name, array $config)
    {
        return $this->customCreators[$config['driver']]($this->app, $name, $config);
    }

    /**
     * Create a session based authentication guard.
     *
     * @param  string  $name
     * @param  array  $config
     * @return \Webkernel\Accounts\SessionGuard
     */
    public function createSessionDriver($name, $config)
    {
        $guard = new SessionGuard(
            $name,
            $this->createUserProvider($config['provider'] ?? null),
            $this->app->make('session.store'),
            rehashOnLogin: Config::get('hashing.rehash_on_login', true),
            timeboxDuration: Config::get('auth.timebox_duration', 200000),
            hashKey: Config::get('app.key'),
        );

        // When using the remember me functionality of the authentication services we
        // will need to be set the encryption instance of the guard, which allows
        // secure, encrypted cookie values to get generated for those cookies.
        $guard->setCookieJar($this->app->make('cookie'));

        $guard->setDispatcher($this->app->make('events'));

        $guard->setRequest($this->app->make(Request::class));

        if (isset($config['remember'])) {
            $guard->setRememberDuration($config['remember']);
        }

        return $guard;
    }

    /**
     * Create a token based authentication guard.
     *
     * @param  string  $name
     * @param  array  $config
     * @return \Webkernel\Accounts\TokenGuard
     */
    public function createTokenDriver($name, $config)
    {
        // The token guard implements a basic API token based guard implementation
        // that takes an API token field from the request and matches it to the
        // user in the database or another persistence layer where users are.
        $guard = new TokenGuard(
            $this->createUserProvider($config['provider'] ?? null),
            $this->app->make(Request::class),
            $config['input_key'] ?? 'api_token',
            $config['storage_key'] ?? 'api_token',
            $config['hash'] ?? false
        );

        return $guard;
    }

    /**
     * Get the guard configuration.
     *
     * @param  string  $name
     * @return array|null
     */
    protected function getConfig($name)
    {
        return Config::get("auth.guards.{$name}");
    }

    /**
     * Get the default authentication driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return Config::get('auth.defaults.guard');
    }

    /**
     * Set the default guard driver the factory should serve.
     *
     * @param  \UnitEnum|string|null  $name
     * @return void
     */
    public function shouldUse($name)
    {
        $name = enum_value($name) ?: $this->getDefaultDriver();

        $this->setDefaultDriver($name);

        $this->userResolver = fn ($name = null) => $this->guard($name)->user();
    }

    /**
     * Set the default authentication driver name.
     *
     * @param  \UnitEnum|string  $name
     * @return void
     */
    public function setDefaultDriver($name)
    {
        Config::set('auth.defaults.guard', enum_value($name));
    }

    /**
     * Register a new callback based request guard.
     *
     * @param  string  $driver
     * @param  callable  $callback
     * @return $this
     */
    public function viaRequest($driver, callable $callback)
    {
        return $this->extend($driver, function () use ($callback) {
            return new RequestGuard($callback, $this->app->make(Request::class), $this->createUserProvider());
        });
    }

    /**
     * Get the user resolver callback.
     *
     * @return \Closure
     */
    public function userResolver()
    {
        return $this->userResolver;
    }

    /**
     * Set the callback to be used to resolve users.
     *
     * @param  \Closure  $userResolver
     * @return $this
     */
    public function resolveUsersUsing(Closure $userResolver)
    {
        $this->userResolver = $userResolver;

        return $this;
    }

    /**
     * Register a custom driver creator Closure.
     *
     * @param  string  $driver
     * @param  \Closure  $callback
     *
     * @param-closure-this  $this  $callback
     *
     * @return $this
     */
    public function extend($driver, Closure $callback)
    {
        try {
            $callback = $this->bindCallbackToSelf($callback) ?? throw new RuntimeException('Unable to bind custom driver callback');
        } catch (ReflectionException $e) {
            throw new RuntimeException('Unable to bind custom driver callback', previous: $e);
        }

        $this->customCreators[$driver] = $callback;

        return $this;
    }

    /**
     * Register a custom provider creator Closure.
     *
     * @param  string  $name
     * @param  \Closure  $callback
     * @return $this
     */
    public function provider($name, Closure $callback)
    {
        $this->customProviderCreators[$name] = $callback;

        return $this;
    }

    /**
     * Determines if any guards have already been resolved.
     *
     * @return bool
     */
    public function hasResolvedGuards()
    {
        return count($this->guards) > 0;
    }

    /**
     * Forget all of the resolved guard instances.
     *
     * @return $this
     */
    public function forgetGuards()
    {
        $this->guards = [];

        return $this;
    }

    /**
     * Set the application instance used by the manager.
     *
     * @param  \Webkernel\Container\Container  $app
     * @return $this
     */
    public function setApplication($app)
    {
        $this->app = $app;

        return $this;
    }

    /**
     * Dynamically call the default driver instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->guard()->{$method}(...$parameters);
    }
}
