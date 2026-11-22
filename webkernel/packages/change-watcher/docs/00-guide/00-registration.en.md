# Registering Dependencies

Each local target has an identifier, ordered absolute source paths, an optional compiler and a revision. Identifiers are keys, not paths. A watcher without a cache store can still compare local or caller-provided resources.

## Local sources

```php
$watcher = new Webkernel\ChangeWatcher\Watcher();
$watcher->watch(
    id: 'documents:help',
    sources: ['/absolute/app/resources/help.html'],
);

$changes = $watcher->watchNow();
if ($changes->hasChanged('documents:help')) {
    // Rebuild the document index owned by this CLI command.
}
if ($changes->hasChanged('/absolute/app/resources/help.html')) {
    // The registered file itself changed.
}
```

Registration captures a baseline. `watchNow()` observes current values once, compares them with the previous pass and advances the baseline. Repeated checks against the returned `ChangeSet` are in-memory lookups. If a target contains multiple sources, its identifier is changed when any source changes. Absolute paths inside a watched directory can be queried individually, including paths removed since the prior pass.

Use specific files and directories. Directory sources are recursive and include their nested entries. A shared path is fingerprinted once per snapshot. Content hashes detect same-size edits with restored timestamps; this requires reading source bytes during each observation.

Paths must be absolute local filesystem paths. Resolve `.` and `..` before registration. Directory symlinks are rejected to prevent ambiguous recursive trees; register the real directory. File symlinks are followed and their destination and contents participate in the fingerprint. Broken symlinks and special files produce errors. UNC paths are not supported.

## Remote and application-owned values

`watchResource($key, $reader)` accepts a callback returning a string. It can read a remote body, ETag, database schema version or any other stable version. The first call establishes a baseline; each `watchNow()` invokes the reader once and compares its return value.

The caller owns transport, authentication, finite timeouts, HTTP status handling and retries. No HTTP client or cURL extension is required by this package. A remote full-body read can be expensive; prefer a trustworthy content version if the remote service provides one. If content changes while the version does not, the watcher cannot detect it.

Environment values and other state are not observed unless a callback returns them. Make version inputs explicit when they affect generated artifacts.

## Compiling artifacts

A `CacheStore` and compiler callback are optional. The callback receives the registered local source paths and returns complete artifact bytes. The watcher fingerprints sources before and after compilation and publishes only a candidate whose observed inputs still match.

The watcher owns artifact paths and its small pointer metadata. The consumer owns the artifact content and how to load it. Source declarations, keys and output fields do not belong to the watcher.

Register compiler files as sources or increment the target revision when compiler behavior changes. PHP classes already loaded in a persistent process cannot be redefined; restart the process when those implementations change.
