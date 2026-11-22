<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\ChangeWatcher;

use Closure;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Throwable;
use Webkernel\ChangeWatcher\CacheStore;
use Webkernel\ChangeWatcher\ChangeSet;
use Webkernel\ChangeWatcher\Publication;
use Webkernel\ChangeWatcher\Registration;
use Webkernel\ChangeWatcher\SourceSnapshot;
use Webkernel\ChangeWatcher\WatchStatus;

/** Observes local or caller-provided resources and delegates artifact compilation. */
final class Watcher
{
    /** @var array<string, Registration> */
    private array $registrations = [];

    /** @var array<string, Closure(): string> */
    private array $resource_readers = [];

    /** @var array<string, string> Baseline fingerprints advanced by watchNow(). */
    private array $observed = [];

    /** @var array<string, Publication> */
    private array $publications = [];

    /** @var array<string, WatchStatus> */
    private array $statuses = [];

    /** @var array<string, Throwable> */
    private array $errors = [];

    /** @var array<string, true> Explicitly replaced registrations must be rebuilt. */
    private array $forced = [];

    private bool $polling = false;

    /**
     * Creates an in-memory observer, optionally backed by a publication store.
     *
     * @param $store CacheStore|null Persistent store required only for compiling artifacts.
     * @param $revision string Registry revision included in all target fingerprints.
     */
    public function __construct(
        private readonly ?CacheStore $store = null,
        private readonly string $revision = '1',
    ) {
    }

    /**
     * Observes local files or directories and optionally compiles changed sources.
     *
     * Registration captures a baseline. Later calls to watchNow() compare against
     * the previous observation, then advance it. A key may be a target identifier
     * or any absolute file path inside a registered directory.
     *
     * @param $id string Unique target identifier.
     * @param $sources list<string> Absolute files or recursively observed directories.
     * @param $compiler callable(list<string>): string|null Optional compiler returning full artifact bytes.
     * @param $revision string Non-empty consumer compiler version.
     * @return void
     * @throws InvalidArgumentException When a target, revision or source path is invalid.
     */
    public function watch(
        string $id,
        array $sources,
        ?callable $compiler = null,
        string $revision = '1',
    ): void {
        $this->assertKey($id);
        if ($revision === '') {
            throw new InvalidArgumentException('A non-empty target revision is required.');
        }
        if ($sources === [] || !array_is_list($sources)) {
            throw new InvalidArgumentException('Register a non-empty list of source paths.');
        }

        $normalized = [];
        foreach ($sources as $source) {
            if (!is_string($source)) {
                throw new InvalidArgumentException('Every source path must be a string.');
            }
            $path = SourceSnapshot::absolutePath($source);
            $resolved = realpath($path);
            $physical = $resolved === false ? $path : SourceSnapshot::absolutePath($resolved);
            if (
                $this->store !== null
                && ($physical === $this->store->directory || str_starts_with($physical, $this->store->directory . '/'))
            ) {
                throw new InvalidArgumentException('Generated cache files cannot be registered as sources.');
            }
            $normalized[] = $path;
        }

        if (isset($this->registrations[$id]) || isset($this->resource_readers[$id])) {
            $this->forced[$id] = true;
        }
        unset($this->resource_readers[$id]);
        $registration = new Registration(
            array_values(array_unique($normalized)),
            $compiler === null ? null : Closure::fromCallable($compiler),
            serialize([$this->revision, $revision]),
        );
        $this->registrations[$id] = $registration;
        $snapshot = $this->newSnapshot();
        $this->observed[$id] = $snapshot->fingerprint($registration);
        foreach ($snapshot->observations() as $path => $fingerprint) {
            $this->observed[$path] = $fingerprint;
        }
        $this->statuses[$id] = WatchStatus::Pending;
        unset($this->errors[$id], $this->publications[$id]);
    }

    /**
     * Observes any string resource through a caller-owned reader.
     *
     * The reader can return file bytes, a remote response, an ETag, or another
     * stable version value. Network clients, timeouts and authentication remain
     * the caller's responsibility.
     *
     * @param $key string Unique resource key checked by ChangeSet::hasChanged().
     * @param $reader callable(): string Returns current resource bytes or version.
     * @return void
     * @throws InvalidArgumentException When a key is invalid.
     * @throws RuntimeException When the reader does not return a string.
     */
    public function watchResource(string $key, callable $reader): void
    {
        $this->assertKey($key);
        if (isset($this->registrations[$key]) || isset($this->resource_readers[$key])) {
            $this->forced[$key] = true;
        }
        unset($this->registrations[$key], $this->publications[$key]);
        $reader = Closure::fromCallable($reader);
        $this->resource_readers[$key] = $reader;
        $this->observed[$key] = $this->readResource($key, $reader);
        $this->statuses[$key] = WatchStatus::Pending;
        unset($this->errors[$key]);
    }

    /**
     * Removes a target from this process without deleting artifacts used by readers.
     *
     * @param $key string Target or resource key.
     * @return void
     */
    public function unwatch(string $key): void
    {
        $removed_sources = $this->registrations[$key]->sources ?? [];
        unset(
            $this->registrations[$key],
            $this->resource_readers[$key],
            $this->observed[$key],
            $this->statuses[$key],
            $this->publications[$key],
            $this->errors[$key],
            $this->forced[$key],
        );

        $remaining_sources = [];
        foreach ($this->registrations as $registration) {
            array_push($remaining_sources, ...$registration->sources);
        }
        foreach (array_keys($this->observed) as $path) {
            if (preg_match('~^(?:[A-Za-z]:/|/)~', $path) !== 1) {
                continue;
            }
            $was_removed = false;
            foreach ($removed_sources as $source) {
                if ($path === $source || str_starts_with($path, rtrim($source, '/') . '/')) {
                    $was_removed = true;
                    break;
                }
            }
            if (!$was_removed) {
                continue;
            }
            foreach ($remaining_sources as $source) {
                if ($path === $source || str_starts_with($path, rtrim($source, '/') . '/')) {
                    $was_removed = false;
                    break;
                }
            }
            if ($was_removed) {
                unset($this->observed[$path]);
            }
        }
    }

    /**
     * Reads all registered resources once and returns a comparison with the prior pass.
     *
     * Repeated hasChanged() calls on the returned value perform no I/O. Each source
     * is read once per pass, including files shared by multiple target registrations.
     *
     * @return ChangeSet Current changes indexed by target key and observed path.
     * @throws RuntimeException When a source or resource reader fails.
     */
    public function watchNow(): ChangeSet
    {
        $snapshot = $this->newSnapshot();
        $changes = [];
        $fingerprints = [];
        $current = [];

        foreach ($this->registrations as $id => $registration) {
            $fingerprint = $snapshot->fingerprint($registration);
            $fingerprints[$id] = $fingerprint;
            $current[$id] = $fingerprint;
            foreach ($snapshot->observations() as $path => $path_fingerprint) {
                $current[$path] = $path_fingerprint;
            }
        }
        foreach ($this->resource_readers as $key => $reader) {
            $current[$key] = $this->readResource($key, $reader);
            $this->statuses[$key] = WatchStatus::Ready;
        }
        foreach ($this->registrations as $id => $registration) {
            if ($registration->compiler === null) {
                $this->statuses[$id] = WatchStatus::Ready;
            }
        }

        $removed_paths = [];
        foreach ($this->observed as $key => $previous) {
            if (!array_key_exists($key, $current) && preg_match('~^(?:[A-Za-z]:/|/)~', $key) === 1) {
                $current[$key] = 'missing';
                $changes[$key] = $previous !== 'missing';
                $removed_paths[] = $key;
            }
        }

        foreach ($current as $key => $fingerprint) {
            if (!array_key_exists($key, $changes)) {
                $changes[$key] = isset($this->observed[$key]) && $this->observed[$key] !== $fingerprint;
            }
            $this->observed[$key] = $fingerprint;
        }
        foreach ($removed_paths as $path) {
            unset($this->observed[$path]);
        }

        return new ChangeSet($changes, $fingerprints);
    }

    /**
     * Returns the current process's last ready artifact without filesystem access.
     *
     * @param $id string Target identifier.
     * @return Publication|null Null when unknown, pending or failed.
     */
    public function current(string $id): ?Publication
    {
        return ($this->statuses[$id] ?? null) === WatchStatus::Ready
            ? ($this->publications[$id] ?? null)
            : null;
    }

    /**
     * Returns the last observed state without polling.
     *
     * @param $id string Target identifier.
     * @return WatchStatus|null Null when unknown.
     */
    public function status(string $id): ?WatchStatus
    {
        return $this->statuses[$id] ?? null;
    }

    /** @return array<string, WatchStatus> */
    public function statuses(): array
    {
        return $this->statuses;
    }

    /** @return array<string, Throwable> */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Checks local compiled targets and atomically publishes changed artifacts.
     *
     * @return array<string, Publication> Newly published artifacts.
     * @throws LogicException When called recursively, without a store, or for a missing compiler.
     * @throws RuntimeException When a different process holds the publisher lock.
     */
    public function poll(): array
    {
        if ($this->polling) {
            throw new LogicException('A compiler cannot recursively poll its watcher.');
        }
        $targets = array_filter($this->registrations, static fn(Registration $item): bool => $item->compiler !== null);
        if ($targets === []) {
            $this->watchNow();
            return [];
        }
        if ($this->store === null) {
            throw new LogicException('A CacheStore is required to compile and publish artifacts.');
        }

        $lock = @fopen($this->store->directory . '/publisher.lock', 'c+b');
        if ($lock === false) {
            throw new RuntimeException('Unable to open the publisher lock.');
        }
        if (!flock($lock, LOCK_EX | LOCK_NB)) {
            fclose($lock);
            throw new RuntimeException('Another publisher is using this cache store.');
        }

        $this->polling = true;
        try {
            $changes = $this->watchNow();
            $published = [];
            foreach ($targets as $id => $registration) {
                $id = (string) $id;
                if (($this->registrations[$id] ?? null) !== $registration) {
                    continue;
                }
                $this->statuses[$id] = WatchStatus::Pending;
                unset($this->errors[$id]);
                try {
                    $fingerprint = $changes->fingerprint($id);
                    if ($fingerprint === null) {
                        continue;
                    }
                    $previous = $this->store->read($id);
                    if (!isset($this->forced[$id]) && $previous?->fingerprint === $fingerprint) {
                        $this->publications[$id] = $previous;
                        $this->statuses[$id] = WatchStatus::Ready;
                        continue;
                    }

                    $contents = ($registration->compiler)($registration->sources);
                    if (!is_string($contents)) {
                        throw new RuntimeException('The compiler must return complete artifact bytes: ' . $id);
                    }
                    if (($this->registrations[$id] ?? null) !== $registration) {
                        continue;
                    }
                    if ($this->newSnapshot()->fingerprint($registration) !== $fingerprint) {
                        continue;
                    }

                    $publication = $this->store->publish($id, $fingerprint, $contents);
                    $this->publications[$id] = $publication;
                    $this->statuses[$id] = WatchStatus::Ready;
                    unset($this->forced[$id]);
                    $published[$id] = $publication;
                } catch (Throwable $error) {
                    if (($this->registrations[$id] ?? null) === $registration) {
                        $this->errors[$id] = $error;
                        $this->statuses[$id] = WatchStatus::Failed;
                    }
                }
            }

            return $published;
        } finally {
            $this->polling = false;
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function newSnapshot(): SourceSnapshot
    {
        return new SourceSnapshot($this->store?->directory ?? '');
    }

    /**
     * Reads and fingerprints one caller-provided resource.
     *
     * @param $key string Resource identifier.
     * @param $reader Closure(): string Resource reader.
     * @return string SHA-256 digest.
     */
    private function readResource(string $key, Closure $reader): string
    {
        $value = $reader();
        if (!is_string($value)) {
            throw new RuntimeException('Resource reader must return a string: ' . $key);
        }

        return hash('sha256', $value);
    }

    private function assertKey(string $key): void
    {
        if ($key === '' || str_contains($key, "\0") || preg_match('//u', $key) !== 1) {
            throw new InvalidArgumentException('Expected a non-empty UTF-8 watch key.');
        }
    }
}
