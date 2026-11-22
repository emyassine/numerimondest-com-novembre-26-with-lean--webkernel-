# Webkernel Config Package

The **Webkernel Config Package** compiles PHP configuration files into a flat dictionary and a tree. A keyed lookup loads only its top-level section; it resolves each requested key from that in-memory tree and memoizes the result. Full configuration operations load the complete compiled cache.

## Key Features

- **Direct Key Lookup ($O(1)$ Hash Lookup)**: Exact keys already resolved are served by PHP's associative array. A different key in the same loaded section traverses only its path segments once; no lookup scans the full key set.
- **Automatic Fingerprint Recompilation**: Zero manual build commands (`config:cache` is obsolete). Automatically detects source file changes across application directories (`config/*.php`), provider packages (`ServiceProvider::CONFIG`), and runtime overrides (`storage/settings/platform-runtime.php`).
- **Dynamic Configuration Discovery**: Discovers configuration files dynamically without hardcoded path enums or filename assumptions.
- **Enhanced Provider Manifest Support**: Supports package `ServiceProvider::CONFIG` definitions with target path, publish destination, and tag metadata.
- **Publishable Assets Management**: Exposes `Config::publishables()` façade method for asset publishing tools.
- **Runtime Key Protection Guards**: Immutable configuration guards prevent accidental runtime mutation of platform-critical settings.
- **OPcache Friendly Atomic Writes**: Writes compiled configuration arrays atomically with immediate OPcache cache invalidation.

---

## Installation & Requirements

- **PHP Version**: `>= 8.5`
- **Dependencies**: `webkernel/lifecycle`

Included automatically within Webkernel core distribution:

```json
{
    "require": {
        "webkernel/config": "^0.12.0"
    }
}
```

---

## Basic Usage

### Reading Configuration Values

Access configuration values using the global `config()` helper function or the `Config` static façade:

```php
use Webkernel\Config\Config;

// Global helper access (direct O(1) lookup after the section is loaded)
$app_name = config('app.app.name');
$debug    = config('app.debug', false);

// Facade access
$database_host = Config::get('database.connections.mysql.host', '127.0.0.1');

// Tree branch access (returns subtree array)
$telemetry_config = config('telemetry.telemetry.structure');
```

`config_path()` resolves the configuration directory held by `ConfigPaths`. Its default is `<project-root>/config`. A framework bootstrap can replace that path before configuration is booted:

```php
use Webkernel\Config\Config;
use Webkernel\Config\ConfigPaths;

Config::configurePaths(new ConfigPaths(
    root_path: base_path(),
    vendor_path: vendor_path(),
    config_directory: 'platform/config',
));
Config::boot();
```

The first keyed `config()` call loads the requested top-level section from the compiled section cache and resolves that key from the in-memory tree. The resolved key is memoized for direct lookups; another key in that loaded section traverses its tree once and is memoized too. PHP-FPM can reuse decoded sections through APCu across requests, checking the section file's metadata and configuration inputs before reuse. Entries expire after five minutes. A different top-level section is loaded separately the first time it is requested. Calling `Config::boot()` explicitly, requesting the repository with `config()` without a key, or calling `Config::all()` loads the complete compiled cache. Discovery and compilation run when the cache is missing or stale. Under PHP-FPM, freshness checks and recompilation run after the response; CLI checks happen at shutdown.

`config_path()` only reads the configured path and appends the requested sub-path. It does not load configuration values. First-load timings therefore include work that `config_path()` never performs.

### Path Resolution Helper

Resolve configuration-defined paths or application sub-paths:

```php
use Webkernel\Config\Config;

// Resolves relative configuration value or fallback to absolute path
$cache_path = Config::path('platform.internal.cache_path');
$custom_dir = Config::path('storage/custom');
$config_file = config_path('app.php');
```

---

## Architecture & Recompilation Engine

```text
First keyed config() call
    ├── Read the requested top-level section cache
    └── Add its flat keys to the in-memory dictionary

Later config() calls
    └── Read the in-memory dictionary

Full configuration request (boot(), all(), or repository())
    └── Read and validate the complete compiled cache
```

### Change Detection & Fingerprinting

The compiled cache records application directories and their nested directories, configuration files, provider definitions, the provider manifest, and runtime overrides. Missing paths are also tracked so their creation can invalidate the cache.

`webkernel/change-watcher` owns the shared source snapshot primitives. Config captures file and directory metadata without reading source contents on a cache hit. Full compiled arrays and decoded sections are kept in APCu when available; otherwise Config reads the compiled PHP cache and section files from disk. Under PHP-FPM, Config serves the current cache first, sends the response, then checks metadata and recompiles if needed. A request concurrent with a source change can use the preceding configuration; the rebuilt cache is available to the next request. If no usable compiled cache exists, Config compiles synchronously. CLI validates at shutdown after producing its output.

Values stay in memory for the current request. Persistent workers must call `Config::flush()` between requests to allow the next access to check freshness. Configuration inputs outside tracked paths, including environment values, are not fingerprinted.

---

## Package Provider Configuration & Publishing

Package providers declare configuration files using the `ServiceProvider::CONFIG` array structure:

```php
namespace Acme\Courier\Providers;

use Webkernel\ServiceProvider;

class CourierProvider extends ServiceProvider
{
    public const CONFIG = [
        'courier' => [
            'path'    => __DIR__ . '/../../config/courier.php',
            'publish' => \config_path('courier.php'),
            'tag'     => 'courier-config',
        ],
    ];
}
```

### Publishing Package Configurations

Asset management tools and CLI commands inspect publishable package assets via the static façade:

```php
use Webkernel\Config\Config;

// Retrieve all registered publishable configurations
$all_publishables = Config::publishables();

// Filter publishable configurations by tag
$courier_publishables = Config::publishables('courier-config');

foreach ($courier_publishables as $item) {
    echo $item->key;     // "courier"
    echo $item->source;  // "/path/to/vendor/courier/config/courier.php"
    echo $item->target;  // "/path/to/app/config/courier.php"
    echo $item->tag;     // "courier-config"
}
```

---

## Configuration Protection & Guards

Prevent accidental runtime mutations of sensitive platform values using `Config::protect()`:

```php
use Webkernel\Config\Config;
use Webkernel\Config\Exceptions\ConfigGuardException;

// Protect exact keys and prefix trees
Config::protect([
    'app.secret',
    'platform',
]);

// Permitted runtime update
Config::set('custom.feature_flag', true);

// Throws ConfigGuardException (exact key match)
Config::set('app.secret', 'new-secret');

// Throws ConfigGuardException (prefix match on "platform.*")
Config::set('platform.debug', false);
```

---

## Performance Benchmarks

Measure cold CLI access, first section load in a PHP-FPM worker, shared APCu section access, full boot and repeated lookups separately. A cold keyed load reads and decodes its section cache; a warm PHP-FPM request checks section metadata and can fetch the decoded section from APCu. Freshness validation is deferred until shutdown. `Config::boot()` reads the complete cache and checks filesystem metadata; a missing or stale cache also includes compilation. A repeated lookup of the same key reads an array already in memory.

The first keyed access target is **90 µs or less**. Cold CLI measurements currently exceed it. PHP-FPM can reuse a decoded section through APCu after its file metadata and configuration inputs match; see the [profiling guide](./docs/40-benchmarks.en.md) for the separate cold, shared-cache and in-memory measurements.

---

## Complete Documentation

For detailed architectural guides and API references, see the [`docs/`](./docs/index.md) directory:

- [Architecture & Fingerprint Engine](./docs/00-architecture.en.md)
- [Configuration & Options](./docs/10-configuration.en.md)
- [Package Provider & Asset Publishing](./docs/20-providers.en.md)
- [Immutability & Guard System](./docs/30-guards.en.md)
- [Benchmark & Profiling Guide](./docs/40-benchmarks.en.md)

---

## Copyright & License

(c) 2025 - 2027 Numerimondes, El Moumen Yassine.  
Released under the proprietary Webkernel license.
