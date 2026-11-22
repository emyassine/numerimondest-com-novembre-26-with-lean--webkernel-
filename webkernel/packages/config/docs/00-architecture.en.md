# Config Architecture & Fingerprint Engine

Configuration files return PHP arrays. During compilation, Webkernel builds a flat dictionary containing every dot-notation key and its value, including array branches. A single-key lookup loads only its top-level section, walks the requested path, then memoizes that exact key. Full boot and repository operations use the flat dictionary directly.

## First access

The first keyed `config('key')` call reads the compact cache for that key's top-level section and resolves the key from its in-memory tree. Config memoizes that value for direct lookup; another requested key in the same section is resolved from the loaded tree once and memoized. A key from another section loads that section once. In PHP-FPM, a decoded section can be reused from APCu across requests after its file metadata and configuration inputs match. Entries expire after five minutes. Config does not load every section on a single-key lookup. `Config::boot()` can also be called explicitly; it reads the full compiled PHP cache and compares its input paths, registered providers and filesystem snapshot with the current state.

When the full cache is current, boot assigns its flat dictionary and tree directly. It does not enumerate directories, load providers, instantiate the compiler or construct a repository. The repository is created only when an operation needs it, such as `config()` without arguments, `Config::all()` or `Config::set()`.

When the cache is missing or stale, discovery selects the sources, compilation merges their values, and an atomic write stores the resulting dictionary, tree and metadata. The compiled cache is not written if the captured filesystem snapshot changes during compilation.

## Source selection and freshness

The cache identifies the configuration roots, explicit provider registrations, vendor directory and runtime override file used to select sources. Changing those inputs prevents reuse of a cache compiled for different inputs.

The snapshot tracks:

- Application configuration roots, nested directories and PHP source files.
- Provider definition files and their declared configuration files.
- The Composer provider manifest, including when it does not exist yet.
- The runtime override file, including when it does not exist yet.

`webkernel/change-watcher` owns the shared source snapshot primitives. Config compares tracked file and directory metadata without reading source contents on a cache hit. Config stores the compiled payload in APCu when available. Under PHP-FPM, Config serves the current compiled cache, sends the response, then checks freshness and recompiles stale sources for the next request. A request concurrent with a source edit may therefore see the preceding compiled configuration. Missing caches and other PHP runtimes use synchronous compilation or validation.

The Config integration compares modification time, size, change time, inode and file mode, and checks each tracked file and directory directly. The standalone `watchNow()` API uses content fingerprints for explicit CLI and caller-provided resource observations. Environment changes and other inputs read by configuration PHP code are not automatically tracked.

## Request lifetime

After a section or full boot is loaded, reads use in-memory values without filesystem checks. A normal PHP request starts with fresh static state. Persistent workers must call `Config::flush()` between requests; the next read then validates the cache again. PHP classes already loaded in a persistent worker cannot be redefined: changing a provider class requires restarting that worker.

`ConfigPaths` owns path configuration. Replacing it through `Config::configurePaths()` clears loaded configuration and updates `config_path()` immediately. Custom configuration directories and explicit provider registrations survive `Config::flush()`.

## Atomic persistence and OPcache

Cache writes use a temporary file in the destination directory, followed by a rename and OPcache invalidation. Readers therefore do not observe a partially written cache file. Source files and the provider manifest have their bytecode invalidated when recompilation reads them.

Atomic writes do not make the full source tree transactional. Avoid editing several configuration files while requests are compiling them when a consistent multi-file deployment is required.

## Performance boundary

Reading and decoding a section cache, then traversing to the requested key, are part of its first cold lookup. New keys in that loaded section do not read another file. Warm PHP-FPM requests can retrieve decoded sections from APCu after a metadata check. Once a key is memoized, PHP's associative array lookup is an average O(1) hash-table access; binary search would add work and require a sorted list. A full boot loads the full PHP cache and validates filesystem metadata; OPcache reduces repeated PHP parsing, but does not eliminate filesystem checks. See [Benchmark & Performance Profiling](./40-benchmarks.en.md) for the separate measurements.
