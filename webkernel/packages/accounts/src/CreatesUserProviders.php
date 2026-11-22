<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Accounts;

use InvalidArgumentException;
use Webkernel\Config\Config;

trait CreatesUserProviders
{
    /**
     * The registered custom provider creators.
     *
     * @var array
     */
    protected $customProviderCreators = [];

    /**
     * Create the user provider implementation for the driver.
     *
     * @param  string|null  $provider
     * @return \Webkernel\Accounts\Contracts\UserProvider|null
     *
     * @throws \InvalidArgumentException
     */
    public function createUserProvider($provider = null)
    {
        if (is_null($config = $this->getProviderConfiguration($provider))) {
            return;
        }

        if (isset($this->customProviderCreators[$driver = ($config['driver'] ?? null)])) {
            return call_user_func(
                $this->customProviderCreators[$driver], $this->app, $config
            );
        }

        return match ($driver) {
            'database' => $this->createDatabaseProvider($config),
            'eloquent' => $this->createEloquentProvider($config),
            default => throw new InvalidArgumentException(
                "Authentication user provider [{$driver}] is not defined."
            ),
        };
    }

    /**
     * Get the user provider configuration.
     *
     * @param  string|null  $provider
     * @return array|null
     */
    protected function getProviderConfiguration($provider)
    {
        if ($provider = $provider ?: $this->getDefaultUserProvider()) {
            return Config::get('auth.providers.'.$provider);
        }
    }

    /**
     * Create an instance of the database user provider.
     *
     * @param  array  $config
     * @return \Webkernel\Accounts\DatabaseUserProvider
     */
    protected function createDatabaseProvider($config)
    {
        return new DatabaseUserProvider(
            $this->app->make('db')->connection($config['connection'] ?? null),
            $this->app->make('hash'),
            $config['table'],
        );
    }

    /**
     * Create an instance of the Eloquent user provider.
     *
     * @param  array  $config
     * @return \Webkernel\Accounts\EloquentUserProvider
     */
    protected function createEloquentProvider($config)
    {
        return new EloquentUserProvider($this->app->make('hash'), $config['model']);
    }

    /**
     * Get the default user provider name.
     *
     * @return string
     */
    public function getDefaultUserProvider()
    {
        return Config::get('auth.defaults.provider');
    }
}
