# Webkernel Change Watcher

`webkernel/change-watcher` tracks changes to local files, remote resources and other caller-provided values. It owns source observation and artifact publication; each consumer owns its data model and compiler.

It runs on PHP 8.5 or newer without third-party dependencies. Local paths use content fingerprints; remote sources use a reader callback provided by the application. No `inotify`, APCu, cURL extension or framework integration is required.

## Installation

```bash
composer require webkernel/change-watcher
```

## Check a local file

```php
use Webkernel\ChangeWatcher\Watcher;

$watcher = new Watcher();
$watcher->watch(
    id: 'documents:help',
    sources: [dirname(__DIR__) . '/app/resources/help.html'],
);

// Later, in the CLI command:
$changes = $watcher->watchNow();
$changed = $changes->hasChanged('documents:help');
// Or query a source path: $changes->hasChanged('/absolute/path/to/help.html');
```

`watch()` records the baseline. `watchNow()` reads each registered resource once, compares it with the previous observation, and advances the baseline. Check the returned `ChangeSet` as many times as needed; `hasChanged()` reads only memory. The first `watchNow()` establishes an observation and reports no changes. A removed path inside a watched directory is reported on the first observation after removal.

## Check a remote resource

The application supplies the HTTP reader, so it chooses a client, authentication, timeouts and error handling:

```php
$watcher->watchResource('remote:vendor-schema', static function (): string {
    $handle = curl_init('https://example.com/schema.json');
    curl_setopt_array($handle, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 3]);
    $contents = curl_exec($handle);
    if ($contents === false) {
        throw new RuntimeException(curl_error($handle));
    }
    curl_close($handle);

    return $contents;
});

$changed = $watcher->watchNow()->hasChanged('remote:vendor-schema');
```

The callback may return a body or a trusted stable version such as an ETag. The package compares exactly the returned strings.

## Compile and publish a cache artifact

Compilation is optional. For consumers that want the watcher to publish a compiled artifact, supply a `CacheStore` and compiler callback:

```php
use Webkernel\ChangeWatcher\CacheStore;
use Webkernel\ChangeWatcher\Watcher;

$watcher = new Watcher(new CacheStore('/absolute/app/runtime/cache/change-watcher'));
$watcher->watch(
    id: 'documents:help',
    sources: ['/absolute/app/resources/help.html'],
    compiler: static fn(array $sources): string => file_get_contents($sources[0]),
);
$watcher->poll();
$publication = $watcher->current('documents:help');
```

The compiler returns complete artifact bytes. Its data format and interpretation belong to the consumer. Publication writes the artifact first and then replaces its pointer atomically.

## Package boundary

Config currently uses `SourceSnapshot` metadata snapshots for cache freshness, avoiding content reads on a cache hit. The explicit `Watcher` API uses content fingerprints for local files and directory trees, giving it a stronger change check at the cost of reading source contents on every poll. Both paths share path normalization and snapshot code; their freshness guarantees are intentionally different.

The watcher does not parse or compile configuration, routes or views. A consumer supplies its source roots and compiler callback, and remains responsible for deciding when compiled data is safe to replace. This lets each package use the same observation and atomic publication primitives without coupling their runtime models.

`new Watcher()` is an in-memory observer. It needs no cache path for `watch()`, `watchResource()` or `watchNow()`. A `CacheStore` is required only for `poll()` when a target has a compiler.

## CLI process

The `change-watcher` binary runs a PHP process that polls explicit definitions. Its interval is a delay after each scan, so detection time includes scan duration. It can reload its definitions file. See [running the watcher](docs/00-guide/10-running.en.md).

## Freshness and performance

Hashing local source content requires reading it. A remote reader may perform network I/O. `watchNow()` carries that cost once per pass; repeated `ChangeSet::hasChanged()` checks do not.

**The 90 µs first-keyed-access target for Config is not established by this package.** Config's keyed lookup reads a section cache and defers metadata validation until shutdown; a full boot checks tracked filesystem metadata. Measure keyed access, full boot, polling, and compilation separately.

## Documentation

- [Registering dependencies](docs/00-guide/00-registration.en.md)
- [Running the watcher](docs/00-guide/10-running.en.md)
- [Freshness and performance](docs/00-guide/20-consistency.en.md)

## License

Proprietary. Copyright (c) 2025 - 2027 Numerimondes, El Moumen Yassine. See [LICENSE](LICENSE).
