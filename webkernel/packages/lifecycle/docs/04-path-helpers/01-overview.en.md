# Path Helpers

Path helpers answer a small question: where does this file belong? They use Composer's project and package metadata so paths do not depend on the working directory or a fixed `vendor/` depth.

## Choose the helper

| Need | Helper | Scope |
| --- | --- | --- |
| A file owned by the root application | `base_path('config/app.php')` | Framework-agnostic. |
| The Composer dependency directory | `vendor_path()` | Framework-agnostic. |
| A package named `webkernel/<name>` | `webkernel_package('codebase')` | Webkernel-specific. |

## Load Composer first

```php
<?php declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

$project_root = base_path();
$dependency_root = vendor_path();
```

Use the actual autoloader location for your project. In the Webkernel scaffold it is `internal/dependencies/autoload.php`. Lifecycle includes its helper definitions through `autoload.files`; you do not require the functions file manually.

## Existing framework helpers

Each function is guarded by `function_exists()`. If another package already defined that function, Lifecycle keeps it. This matters in frameworks that already expose `base_path()`: the existing implementation retains its own semantics. The descriptions and measurements here apply to Lifecycle's implementation when it owns the function name.

## Appending a path

Pass a relative suffix without a leading slash. Helpers append the suffix; they do not check that the resulting file exists, normalize `..`, create directories or enforce that a suffix stays inside the base directory. Validate user-supplied paths before using them in file operations.

## Next step

You can read:
- [`base_path()`](02-base-path.en.md)
- [`vendor_path()`](03-vendor-path.en.md)
- [`webkernel_package()`](04-webkernel-package.en.md)

For cache behavior and measurement context, you can also read [Performance](05-performance.en.md).
