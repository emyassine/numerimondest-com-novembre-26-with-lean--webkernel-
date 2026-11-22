# `webkernel_package()`

`webkernel_package()` locates an installed package in the `webkernel/*` namespace. It follows Composer metadata, including custom install paths and symlinked path repositories.

## Usage

```php
webkernel_package('lifecycle');
webkernel_package('lifecycle', 'README.md');
webkernel_package('codebase', 'src');
```

| Parameter | Meaning |
| --- | --- |
| `$package` | Short name, such as `lifecycle`; the helper adds `webkernel/`. |
| `$sub_path` | Optional relative suffix. `null` returns the package directory without a trailing slash. |

The signature is `webkernel_package(string $package, ?string $sub_path = null): string`. Passing an empty string as the suffix returns the cached directory with its trailing slash. Do not pass the full `webkernel/lifecycle` name as the first argument.

## Resolution and cache

1. Ask `Composer\InstalledVersions::getInstallPath()` for `webkernel/<name>`.
2. Canonicalize the returned path with `realpath()` and require an existing directory.
3. Cache that directory by short package name for the process.
4. Append the optional suffix without checking that the resulting file exists.

With a symlinked path repository, the canonical result may be the source checkout rather than the visible dependency symlink. The result still comes from Composer's registered installation location; it does not assume a repository layout.

## Errors

| Condition | Behavior |
| --- | --- |
| Package is not registered | `RuntimeException` with the package name and caller file/line. |
| Composer returns no install path | `RuntimeException` because a real directory cannot be resolved. |
| Registered path is missing or is not a directory | `RuntimeException` with the registered path and caller context. |
| A previously cached directory changes later | The helper retains its cached string; it does not revalidate on every call. |

## Scope

This helper is Webkernel-specific. Use `Composer\InstalledVersions` directly for arbitrary package names. For hot application paths, prefer a prepared application path or compiled manifest over repeated discovery. Warm calls are cached, but the first call for each package includes metadata lookup and filesystem canonicalization.

## Next step

See [Performance](05-performance.en.md) for first-call and repeated-call measurements.
