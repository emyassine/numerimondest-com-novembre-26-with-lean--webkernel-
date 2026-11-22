<?php

namespace Webkernel\Platform\Resources\Resource\Concerns;

use Closure;
use Exception;
use Webkernel\Platform\Facades\Platform;
use Webkernel\Platform\Panel;
use Webkernel\Platform\Resources\ResourceConfiguration;

/**
 * @template TConfiguration of ResourceConfiguration = ResourceConfiguration
 */
trait HasConfiguration
{
    /**
     * @var ?class-string<TConfiguration>
     */
    protected static ?string $configurationClass = null;

    /**
     * @return TConfiguration
     */
    public static function make(string $key = 'default'): ResourceConfiguration
    {
        if (! static::$configurationClass) {
            throw new Exception('Resource ' . static::class . ' does not define a $configurationClass.');
        }

        return static::$configurationClass::make(static::class, $key);
    }

    /**
     * @return ?TConfiguration
     */
    public static function getConfiguration(?Panel $panel = null): ?ResourceConfiguration
    {
        $key = Platform::getCurrentResourceConfigurationKey();

        if ($key === null) {
            return null;
        }

        $panel ??= Platform::getCurrentPanel();

        return $panel->getResourceConfiguration(static::class, $key);
    }

    public static function hasConfiguration(): bool
    {
        return static::getConfiguration() !== null;
    }

    /**
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public static function withConfiguration(string $key, Closure $callback): mixed
    {
        $configuration = Platform::getCurrentPanel()->getResourceConfiguration(static::class, $key);

        if (! $configuration) {
            throw new Exception("Configuration '{$key}' not found for resource " . static::class);
        }

        $previousKey = Platform::getCurrentResourceConfigurationKey();

        Platform::setCurrentResourceConfigurationKey($key);

        try {
            return $callback();
        } finally {
            Platform::setCurrentResourceConfigurationKey($previousKey);
        }
    }
}
