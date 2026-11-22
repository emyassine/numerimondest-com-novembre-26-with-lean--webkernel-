# `vendor_path()`

`vendor_path()` returns the directory associated with Composer's registered autoloader. Use it when you need the dependency directory itself and want to respect a custom `vendor-dir`.

## Usage

```php
vendor_path();
vendor_path('composer/installed.php');
vendor_path('autoload.php');
```

| Signature | Default | Return |
| --- | --- | --- |
| `vendor_path(string $path = ''): string` | No suffix. | Cached vendor directory, optionally followed by `/` and the suffix. |

In the Webkernel scaffold, the directory is `internal/dependencies/` under the application. In a conventional Composer project it is usually `vendor/`. Neither directory name is hardcoded in the helper.

## How it resolves the path

The first call reads the first key of `Composer\Autoload\ClassLoader::getRegisteredLoaders()`. The result is cached in a static variable for later calls. The helper does not call `realpath()` and does not reread project configuration on each call.

In a normal application there is one relevant Composer loader. If your process registers several independent Composer loaders, this function follows the first loader in Composer's registered-loader map at the time of the first call. It does not select a loader by the calling class, and later registration changes do not reset the cache.

## When to use something else

For a particular package's location, use `Composer\InstalledVersions::getInstallPath()` or [`webkernel_package()`](04-webkernel-package.en.md). Packages can use custom install paths or symlinks, so appending a package name to the vendor directory is not a reliable general package-location strategy.

For application-owned files, use [`base_path()`](02-base-path.en.md). An existing `vendor_path()` function keeps its own implementation because Lifecycle only defines missing functions.

## Next step

Read [`webkernel_package()`](04-webkernel-package.en.md) for package-aware path resolution.
