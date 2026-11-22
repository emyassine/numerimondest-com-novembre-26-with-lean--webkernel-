# Installation

## 1. Require the package

```sh
composer require webkernel/lifecycle
```

In a Webkernel application you usually do not need this step: `webkernel/codebase` requires Lifecycle, so it is installed transitively.

## 2. Allow the plugin

Composer asks for explicit permission before activating a plugin. Make the setting durable in the root project's `composer.json`:

```json
{
  "config": {
    "allow-plugins": {
      "webkernel/lifecycle": true
    }
  }
}
```

## 3. Load the autoloader

Helpers are loaded through Composer's autoloader. Load it before calling any helper:

```php
<?php declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';
```

In the Webkernel scaffold, the autoloader is at `internal/dependencies/autoload.php`, so the application bootstrap includes that path instead.

## What happens on activation

On activation, Lifecycle:

1. enables Composer's optimized and APCu autoloader settings;
2. initializes the project base path from Composer package metadata;
3. registers its custom installer (`LCBaseInstaller`).

Composer still generates the autoload maps. APCu availability depends on the PHP process running Composer.

These activation settings apply to the Composer process; they do not add a second application bootstrap or rewrite the root manifest's settings. For a project that already defines global path helpers, Lifecycle only defines the missing function names.

## Verify the installation

```sh
composer dump-autoload
```

The command should complete without a plugin-blocked message. After the autoloader is loaded, Lifecycle's `base_path()` should return the canonical project root. If another framework owns that helper name, its implementation continues to apply. If something fails, see [Troubleshooting](../08-troubleshooting.en.md).

## Next step

Run your [first lifecycle hook](04-quick-start.en.md).
