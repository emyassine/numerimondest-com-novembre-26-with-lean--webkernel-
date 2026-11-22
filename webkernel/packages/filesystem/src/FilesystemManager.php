<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
// Adapted from Laravel; see LICENSE-LARAVEL.md.

namespace Webkernel\Filesystem;

use Closure;
use InvalidArgumentException;
use LogicException;
use Psr\Log\LoggerInterface;
use Webkernel\Filesystem\Contracts\Factory;
use Webkernel\Filesystem\Contracts\Filesystem as FilesystemContract;
use Webkernel\Filesystem\Storage\Filesystem as StorageEngine;
use Webkernel\Filesystem\Storage\Local\LocalFilesystemAdapter as LocalAdapter;
use Webkernel\Filesystem\Storage\UnixVisibility\PortableVisibilityConverter;
use Webkernel\Filesystem\Storage\Visibility;
use Webkernel\Utilities\Arr;
use function Webkernel\Utilities\enum_value;

/** Resolves configured disks without an application container or runtime discovery. */
class FilesystemManager implements Factory
{
    /** @var array<string, FilesystemContract> */
    private array $disks = [];

    /** @var array<string, Closure> */
    private array $creators = [];

    /** @var array<string, true> */
    private array $resolving = [];

    private int $ondemand = 0;

    public function __construct(private array $configuration, private ?LoggerInterface $logger = null)
    {
    }

    public function disk($name = null): FilesystemContract
    {
        $name = enum_value($name) ?? $this->getDefaultDriver();
        if (! is_string($name) || $name === '') {
            throw new InvalidArgumentException('A filesystem disk name is required.');
        }

        return $this->disks[$name] ??= $this->resolve($name, $this->configuration['disks'][$name] ?? []);
    }

    public function drive($name = null): FilesystemContract
    {
        return $this->disk($name);
    }

    public function cloud(): FilesystemContract
    {
        return $this->disk($this->getDefaultCloudDriver());
    }

    public function getDefaultDriver(): string
    {
        return $this->configuration['default'] ?? 'local';
    }

    public function getDefaultCloudDriver(): string
    {
        return $this->configuration['cloud'] ?? 's3';
    }

    public function build(string|array $config): FilesystemContract
    {
        return $this->resolve('ondemand.'.++$this->ondemand, is_string($config) ? ['driver' => 'local', 'root' => $config] : $config);
    }

    private function resolve(string $name, array $config): FilesystemContract
    {
        if (isset($this->resolving[$name])) {
            throw new LogicException("Circular filesystem disk dependency at [{$name}].");
        }
        $this->resolving[$name] = true;
        try {
            $driver = $config['driver'] ?? '';
            $disk = isset($this->creators[$driver])
                ? ($this->creators[$driver])($this, $config)
                : match ($driver) {
                    'local' => $this->createLocalDriver($config, $name),
                    'read-through' => $this->createReadThroughDriver($config, $name),
                    default => throw new InvalidArgumentException("Filesystem driver [{$driver}] is not registered for [{$name}]."),
                };
            if (! $disk instanceof FilesystemContract) {
                throw new LogicException("Filesystem driver [{$driver}] must implement the filesystem contract.");
            }
            if ($disk instanceof FilesystemAdapter) {
                $disk->withFactory($this)->withLogger($this->logger);
            }
            return $disk;
        } finally {
            unset($this->resolving[$name]);
        }
    }

    public function createLocalDriver(array $config, string $name = 'local'): LocalFilesystemAdapter
    {
        $root = $config['root'] ?? '';
        if (! is_string($root) || $root === '') {
            throw new InvalidArgumentException('A local disk requires a root directory.');
        }
        // These restrictions must never silently disappear while their adapters are unavailable.
        if (($config['read-only'] ?? false) || ! empty($config['prefix'])) {
            throw new InvalidArgumentException('Read-only and prefixed disks require a registered driver.');
        }
        $visibility = PortableVisibilityConverter::fromArray(
            $config['permissions'] ?? [],
            $config['directory_visibility'] ?? $config['visibility'] ?? Visibility::PRIVATE,
        );
        $adapter = new LocalAdapter(
            $root, $visibility, $config['lock'] ?? LOCK_EX,
            ($config['links'] ?? null) === 'skip' ? LocalAdapter::SKIP_LINKS : LocalAdapter::DISALLOW_LINKS,
            lazyRootCreation: $config['lazy_root_creation'] ?? false,
        );
        return (new LocalFilesystemAdapter(new StorageEngine($adapter, $config), $adapter, $config))
            ->diskName($name)->withFactory($this)->withLogger($this->logger);
    }

    public function createReadThroughDriver(array $config, string $name = 'read-through'): ReadThroughFilesystem
    {
        if (($config['read-only'] ?? false) || ! empty($config['prefix'])) {
            throw new InvalidArgumentException('Read-only and prefixed disks require a registered driver.');
        }
        if (empty($config['primary']) || empty($config['fallback']) || $config['primary'] === $config['fallback']) {
            throw new InvalidArgumentException('A read-through disk requires distinct primary and fallback disks.');
        }
        $primary = is_array($config['primary']) ? $this->build($config['primary']) : $this->disk($config['primary']);
        $fallback = is_array($config['fallback']) ? $this->build($config['fallback']) : $this->disk($config['fallback']);
        if (! $primary instanceof FilesystemAdapter || ! $fallback instanceof FilesystemAdapter) {
            throw new InvalidArgumentException('Read-through disks require storage adapters.');
        }
        $adapter = new ReadThroughFilesystemAdapter(
            $primary->getDriver(), $fallback->getDriver(),
            $config['throw_on_promotion_failure'] ?? false, $config['copy'] ?? true,
        );
        return (new ReadThroughFilesystem(new StorageEngine($adapter, $config), $primary->getAdapter(),
            array_replace($primary->getConfig(), $config), $primary, $fallback))
            ->withFactory($this)->withLogger($this->logger);
    }

    /** @param $callback Closure(self, array): FilesystemContract */
    public function extend(string $driver, Closure $callback): static
    {
        $this->creators[$driver] = $callback;
        return $this;
    }

    public function set($name, FilesystemContract $disk): static
    {
        $this->disks[enum_value($name)] = $disk;
        return $this;
    }

    public function forgetDisk($disk): static
    {
        foreach (Arr::wrap($disk) as $name) {
            unset($this->disks[enum_value($name)]);
        }
        return $this;
    }

    public function purge($name = null): void
    {
        $this->forgetDisk($name ?? $this->getDefaultDriver());
    }

    public function __call(string $method, array $parameters): mixed
    {
        return $this->disk()->{$method}(...$parameters);
    }
}
