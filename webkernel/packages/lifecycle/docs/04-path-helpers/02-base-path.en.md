# `base_path()`

`base_path()` returns the root Composer project's path. Use it for files owned by the application, such as configuration, routes or generated runtime files.

## Usage

```php
base_path();
base_path('config/app.php');
base_path('runtime/build/manifest.json');
```

| Signature | Default | Return |
| --- | --- | --- |
| `base_path(string $path = ''): string` | No suffix. | Prepared project root, optionally followed by `/` and the suffix. |

For a root installed at `/srv/example`, `base_path('config/app.php')` returns `/srv/example/config/app.php`. It returns a string even if the file does not exist.

## Why the result is stable

The first `base_path()` call initializes the root from `Composer\InstalledVersions::getRootPackage()['install_path']` and resolves it with `realpath()`. During plugin activation and Composer events, `LCBasePathInjector` instead uses the directory containing Composer's root configuration file. It stores the result in `$GLOBALS['BASE_PATH']`. Loading the helper definitions does not require installation metadata, so Lifecycle can activate during a first installation.

Changing the process's working directory does not change this root. The built-in `EventSetBasePath` worker refreshes it after autoload generation. Avoid assigning the global yourself; let Composer metadata own the value.

## Cost and errors

After injection, each call reads the global and optionally concatenates a suffix. There is no per-call directory scan or filesystem lookup. The initial canonicalization belongs to autoload initialization and is not included in a benchmark that starts after autoload.

If the root cannot be resolved, the injector throws `RuntimeException` during initialization. The helper assumes the autoloader has completed that setup. If an existing framework owns `base_path()`, its behavior applies instead.

## Next step

Use [`vendor_path()`](03-vendor-path.en.md) when the dependency directory, rather than the application root, is the intended location.
