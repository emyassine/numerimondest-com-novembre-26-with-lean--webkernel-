# Composer Runtime Metadata

Composer's generated autoloader makes `Composer\InstalledVersions` available to application code. Use it when code needs an installed package's identity or location without assuming a dependency-directory layout.

## Package lookup

```php
<?php declare(strict_types=1);

use Composer\InstalledVersions;

require __DIR__ . '/vendor/autoload.php';

if (InstalledVersions::isInstalled('acme/billing')) {
    $package_path = InstalledVersions::getInstallPath('acme/billing');
}
```

Lifecycle's `webkernel_package()` wraps this lookup for the `webkernel/*` namespace and adds canonicalization and a per-package cache.

`base_path()` uses root package metadata during initialization.

## Useful APIs

| API | Question |
| --- | --- |
| `isInstalled()` | Is this package or provided package available? |
| `getPrettyVersion()`, `getVersion()` | Which version is recorded? |
| `getReference()` | Which source/dist reference is recorded? |
| `getInstallPath()` | Where is the package installed? |
| `getInstalledPackagesByType()` | Which installed packages have a given type? |

Install paths can contain symlinks or relative segments. Some package kinds have no physical install directory. A missing package lookup can throw `OutOfBoundsException`; a registered metapackage can return `null` for its path. Handle those cases before opening files.

## API availability

`composer-plugin-api` and `composer-runtime-api` describe different boundaries. Lifecycle's manifest requires the plugin API to integrate with Composer. A library that relies on a particular runtime utility should declare the runtime API version it needs. See Composer's official [runtime utilities](https://getcomposer.org/doc/07-runtime.md) for version availability and method details.

## Build-time discovery

Package metadata lookup is useful during manifest generation. Read the installed package set during Composer/build work, generate the application-specific result and let requests consume that result. The existence of a runtime metadata API does not require scanning the package set during every HTTP request.

## Next step

Read [Built-in Workers](03-built-in-workers.en.md) for how Lifecycle uses its own Composer events.
