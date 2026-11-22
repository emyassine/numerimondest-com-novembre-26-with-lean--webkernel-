<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Cache;

use InvalidArgumentException;
use Webkernel\Cache\ArrayStore;
use Webkernel\Cache\FileStore;
use Webkernel\Cache\Repository;
use Webkernel\Config\Config;
use Webkernel\Filesystem\Filesystem;

/** Resolves the native persistent file cache and isolated in-memory stores. */
final class CacheManager
{
    private array $stores = [];

    public function store(?string $name = null): Repository
    {
        $name ??= (string) Config::get('cache.default', 'file');
        if (isset($this->stores[$name])) { return $this->stores[$name]; }
        $configuration = Config::get('cache.stores.'.$name, ['driver' => $name]);
        $store = match ($configuration['driver'] ?? $name) {
            'file' => new FileStore(new Filesystem(), $configuration['path'] ?? base_path('runtime/cache/data')),
            'array' => new ArrayStore(),
            default => throw new InvalidArgumentException('Unsupported cache store ['.$name.'].'),
        };
        return $this->stores[$name] = new Repository($store, ['store' => $name]);
    }

    public function driver(?string $name = null): Repository { return $this->store($name); }
    public function __call(string $method, array $arguments): mixed { return $this->store()->{$method}(...$arguments); }
    public function getDefaultDriver(): string { return (string) Config::get('cache.default', 'file'); }
}
