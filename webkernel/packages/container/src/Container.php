<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Container;

use Closure;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use Throwable;

/** PSR-11 container with explicit bindings and constructor autowiring. */
final class Container implements ContainerInterface
{
    private static ?self $instance = null;

    /** @var array<string, array{concrete: string|callable, lifetime: 'transient'|'singleton'|'scoped'}> */
    private array $bindings = [];

    /** @var array<string, object> */
    private array $instances = [];

    /** @var array<string, object> */
    private array $scoped_instances = [];

    /** @var list<callable> Release callbacks for process state outside the container. */
    private array $scope_reset_callbacks = [];

    /** @var array<string, string> */
    private array $aliases = [];

    /** @var list<string> */
    private array $resolving = [];

    /** @var array<string, true> */
    private array $resolved = [];

    /**
     * Creates the process container once from the Composer-compiled binding map.
     *
     * @param $bindings array<string, string|array{concrete?: string, lifetime?: 'transient'|'singleton'|'scoped', alias?: string}>
     */
    public static function boot(array $bindings = []): self
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $container = new self();
        foreach ($bindings as $abstract => $declaration) {
            if (! is_string($abstract) || (! is_string($declaration) && ! is_array($declaration))) {
                throw new ResolutionException('Compiled container bindings have an invalid shape.');
            }
            if (is_array($declaration) && isset($declaration['alias'])) {
                if (count($declaration) !== 1 || ! is_string($declaration['alias'])) {
                    throw new ResolutionException(sprintf('Invalid compiled alias for "%s".', $abstract));
                }
                $container->alias($declaration['alias'], $abstract);
                continue;
            }
            $concrete = is_string($declaration) ? $declaration : ($declaration['concrete'] ?? null);
            $lifetime = is_string($declaration) ? 'transient' : ($declaration['lifetime'] ?? 'transient');
            if (! is_string($concrete) || ! is_string($lifetime)) {
                throw new ResolutionException(sprintf('Invalid compiled binding for "%s".', $abstract));
            }
            $container->bind($abstract, $concrete, $lifetime);
        }
        $container->instance(self::class, $container);
        $container->instance(ContainerInterface::class, $container);

        return self::$instance = $container;
    }

    public static function getInstance(): self
    {
        return self::$instance ?? self::boot();
    }

    /** Clears the process instance, primarily for isolated application lifecycles. */
    public static function flush(): void
    {
        self::$instance = null;
    }

    /**
     * Registers a service binding.
     *
     * @param $abstract string Service identifier.
     * @param $concrete string|callable|null Concrete class or factory.
     * @param $lifetime 'transient'|'singleton'|'scoped' Instance lifetime.
     */
    public function bind(string $abstract, string|callable|null $concrete = null, string $lifetime = 'transient'): void
    {
        $this->assertIdentifier($abstract);
        $concrete ??= $abstract;
        if (! in_array($lifetime, ['transient', 'singleton', 'scoped'], true)) {
            throw new ResolutionException(sprintf('Unknown service lifetime: %s', $lifetime));
        }
        $this->bindings[$abstract] = ['concrete' => $concrete, 'lifetime' => $lifetime];
        unset($this->instances[$abstract], $this->scoped_instances[$abstract]);
    }

    /** Registers a shared instance for the lifetime of this process. */
    public function singleton(string $abstract, string|callable|null $concrete = null): void
    {
        $this->bind($abstract, $concrete, 'singleton');
    }

    public function singletonIf(string $abstract, string|callable|null $concrete = null): void
    {
        if (! $this->bound($abstract)) {
            $this->singleton($abstract, $concrete);
        }
    }

    /** Checks explicit registrations without triggering autowiring. */
    public function bound(string $abstract): bool
    {
        $is_alias = isset($this->aliases[$abstract]);
        $abstract = $this->normalizeAlias($abstract);
        return $is_alias || isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    public function resolved(string $abstract): bool
    {
        return isset($this->resolved[$this->normalizeAlias($abstract)]);
    }

    /** Registers an instance shared only until the next request scope begins. */
    public function scoped(string $abstract, string|callable|null $concrete = null): void
    {
        $this->bind($abstract, $concrete, 'scoped');
    }

    /** Registers an already-created shared instance. */
    public function instance(string $abstract, object $instance): object
    {
        $this->assertIdentifier($abstract);
        unset($this->bindings[$abstract], $this->scoped_instances[$abstract]);

        $this->resolved[$abstract] = true;

        return $this->instances[$abstract] = $instance;
    }

    /** Registers a service alias. */
    public function alias(string $abstract, string $alias): void
    {
        $this->assertIdentifier($abstract);
        $this->assertIdentifier($alias);
        if ($abstract === $alias) {
            throw new ResolutionException('A service cannot alias itself.');
        }
        $this->aliases[$alias] = $abstract;
    }

    /** Starts a request scope by releasing all scoped instances. */
    public function beginScope(): void
    {
        $this->scoped_instances = [];

        //> Process-level state owned by a package (for example the active panel)
        //> must not survive into the next request of a long-running worker.
        foreach ($this->scope_reset_callbacks as $callback) {
            $callback();
        }
    }

    /** Registers a callback invoked whenever a new request scope begins. */
    public function onScopeReset(callable $callback): void
    {
        $this->scope_reset_callbacks[] = $callback;
    }

    /** @inheritDoc */
    public function get(string $id): mixed
    {
        if (! isset($this->aliases[$id])) {
            if (isset($this->instances[$id])) {
                return $this->instances[$id];
            }
            if (isset($this->scoped_instances[$id])) {
                return $this->scoped_instances[$id];
            }
        }

        return $this->resolve($id, []);
    }

    /** @inheritDoc */
    public function has(string $id): bool
    {
        try {
            $id = $this->normalizeAlias($id);
        } catch (ResolutionException) {
            return false;
        }

        return isset($this->bindings[$id]) || isset($this->instances[$id]) || class_exists($id);
    }

    /** Resolves one service with named constructor overrides. */
    public function make(string $id, array $parameters = []): mixed
    {
        return $this->resolve($id, $parameters);
    }

    /**
     * Reports the configured application environment.
     *
     * @return ($environments is empty ? string : bool)
     */
    public function environment(string ...$environments): string|bool
    {
        $current = (string) \Webkernel\Config\Config::get('app.env', 'prod');

        if ($environments === []) {
            return $current;
        }

        foreach ($environments as $environment) {
            if ($environment === $current || ($environment === 'production' && $current === 'prod')) {
                return true;
            }
        }

        return false;
    }

    /** Reports whether the application is running its test suite. */
    public function runningUnitTests(): bool
    {
        return $this->environment('testing') === true;
    }

    /**
     * Invokes a callable and resolves typed class parameters from the container.
     *
     * @param $callback callable|array{string|object, string}|string Callback, invokable class, or Class::method.
     * @param $parameters array<string, mixed> Explicit named parameter values.
     */
    public function call(callable|array|string $callback, array $parameters = []): mixed
    {
        $callable = $this->normalizeCallable($callback);
        if (is_array($callable)) {
            $reflection = new ReflectionMethod($callable[0], $callable[1]);
        } elseif ($callable instanceof Closure) {
            $reflection = new ReflectionFunction($callable);
        } elseif (is_string($callable)) {
            $reflection = new ReflectionFunction($callable);
        } else {
            $reflection = new ReflectionMethod($callable, '__invoke');
        }

        return call_user_func_array($callable, $this->resolveParameters($reflection->getParameters(), $parameters));
    }

    /**
     * @param $id string Service identifier.
     * @param $parameters array<string, mixed> Constructor overrides.
     */
    private function resolve(string $id, array $parameters): mixed
    {
        $abstract = $this->normalizeAlias($id);
        $binding = $this->bindings[$abstract] ?? null;
        $lifetime = $binding['lifetime'] ?? 'transient';
        if ($parameters === []) {
            if ($lifetime === 'singleton' && isset($this->instances[$abstract])) {
                return $this->instances[$abstract];
            }
            if ($lifetime === 'scoped' && isset($this->scoped_instances[$abstract])) {
                return $this->scoped_instances[$abstract];
            }
            if (isset($this->instances[$abstract])) {
                return $this->instances[$abstract];
            }
        }

        if (in_array($abstract, $this->resolving, true)) {
            throw new ResolutionException('Circular dependency detected: ' . implode(' -> ', [...$this->resolving, $abstract]));
        }

        $this->resolving[] = $abstract;
        try {
            $concrete = $binding['concrete'] ?? $abstract;
            $object = $this->build($concrete, $parameters);
        } catch (Throwable $exception) {
            if ($exception instanceof ResolutionException || $exception instanceof EntryNotFoundException) {
                throw $exception;
            }
            throw new ResolutionException(sprintf('Unable to resolve service "%s".', $abstract), 0, $exception);
        } finally {
            array_pop($this->resolving);
        }

        if (! is_object($object)) {
            throw new ResolutionException(sprintf('Service factory for "%s" did not return an object.', $abstract));
        }
        $this->resolved[$abstract] = true;
        if ($parameters === [] && $lifetime === 'singleton') {
            $this->instances[$abstract] = $object;
        } elseif ($parameters === [] && $lifetime === 'scoped') {
            $this->scoped_instances[$abstract] = $object;
        }

        return $object;
    }

    /** @param $concrete string|callable Concrete class or factory. @param $parameters array<string, mixed> Named overrides. */
    private function build(string|callable $concrete, array $parameters): mixed
    {
        if (is_callable($concrete) && ! is_string($concrete)) {
            return $this->call($concrete);
        }
        if (is_string($concrete) && ! class_exists($concrete)) {
            if (is_callable($concrete)) {
                return $this->call($concrete);
            }
            throw new EntryNotFoundException(sprintf('Service class does not exist: %s', $concrete));
        }
        if (! is_string($concrete)) {
            throw new ResolutionException('A service binding must resolve to a class or callable.');
        }

        try {
            $class = new ReflectionClass($concrete);
        } catch (Throwable $exception) {
            throw new EntryNotFoundException(sprintf('Service class cannot be reflected: %s', $concrete), 0, $exception);
        }
        if (! $class->isInstantiable()) {
            throw new EntryNotFoundException(sprintf('Service class is not instantiable: %s', $concrete));
        }
        $constructor = $class->getConstructor();
        if ($constructor === null) {
            return $class->newInstance();
        }

        return $class->newInstanceArgs($this->resolveParameters($constructor->getParameters(), $parameters));
    }

    /** @param list<ReflectionParameter> $parameters @param array<array-key, mixed> $provided @return array<string, mixed> */
    private function resolveParameters(array $parameters, array $provided): array
    {
        $resolved = [];
        $position = 0;
        foreach ($parameters as $parameter) {
            $name = $parameter->getName();
            if (array_key_exists($name, $provided)) {
                $resolved[$name] = $provided[$name];
                if (array_key_exists($position, $provided)) {
                    $position++;
                }
                continue;
            }
            if (array_key_exists($position, $provided)) {
                $resolved[$name] = $provided[$position++];
                continue;
            }
            if ($parameter->isVariadic()) {
                continue;
            }

            $class_name = $this->className($parameter->getType());
            if ($class_name !== null) {
                if ($class_name === self::class || $class_name === ContainerInterface::class) {
                    $resolved[$name] = $this;
                    continue;
                }
                if ($this->has($class_name)) {
                    $resolved[$name] = $this->get($class_name);
                    continue;
                }
            }
            if ($parameter->isDefaultValueAvailable()) {
                $resolved[$name] = $parameter->getDefaultValue();
                continue;
            }
            if ($parameter->allowsNull()) {
                $resolved[$name] = null;
                continue;
            }

            $owner = $parameter->getDeclaringClass()?->getName() ?? $parameter->getDeclaringFunction()->getName();
            throw new EntryNotFoundException(sprintf('Cannot resolve parameter $%s for %s.', $name, $owner));
        }

        return $resolved;
    }

    private function className(?ReflectionType $type): ?string
    {
        if ($type instanceof ReflectionNamedType) {
            return $type->isBuiltin() ? null : $type->getName();
        }
        if ($type === null || ! method_exists($type, 'getTypes')) {
            return null;
        }
        foreach ($type->getTypes() as $candidate) {
            if ($candidate instanceof ReflectionNamedType && ! $candidate->isBuiltin()) {
                return $candidate->getName();
            }
        }

        return null;
    }

    private function normalizeCallable(callable|array|string $callback): callable
    {
        if (is_string($callback) && str_contains($callback, '::')) {
            [$class, $method] = explode('::', $callback, 2);
            if (class_exists($class) && method_exists($class, $method)) {
                return [$this->get($class), $method];
            }
        }
        if (is_array($callback) && isset($callback[0], $callback[1]) && is_string($callback[0])) {
            $callback[0] = $this->get($callback[0]);
        }
        if (is_string($callback) && class_exists($callback)) {
            return $this->get($callback);
        }
        if (! is_callable($callback)) {
            throw new ResolutionException('The requested callback is not callable.');
        }

        return $callback;
    }

    private function normalizeAlias(string $id): string
    {
        $seen = [];
        while (isset($this->aliases[$id])) {
            if (isset($seen[$id])) {
                throw new ResolutionException('Circular service alias detected.');
            }
            $seen[$id] = true;
            $id = $this->aliases[$id];
        }

        return $id;
    }

    private function assertIdentifier(string $id): void
    {
        if (trim($id) === '') {
            throw new ResolutionException('A service identifier cannot be empty.');
        }
    }
}
