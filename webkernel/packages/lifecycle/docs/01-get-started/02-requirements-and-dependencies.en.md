# Requirements and Dependencies

## Requirements

| Requirement | Version | Notes |
| --- | --- | --- |
| PHP | 8.5 or newer | Applies to Composer and the application using the package. Their PHP binaries can differ; check both. |
| Composer Plugin API | `^2.9` (2.9 or newer within major 2) | Declared by the package as a `composer-plugin`. |
| Composer | A version providing the Plugin API above | Needed to install and activate the plugin. |

## Optional capabilities

| Capability | Purpose | Responsibility |
| --- | --- | --- |
| APCu (in the Composer PHP process) | Lets Composer use its APCu autoloader setting. | You enable it; Lifecycle only reports the effective state. |
| `composer-runtime-api` | Needed by a library that depends on a specific `Composer\InstalledVersions` API version. | Declared by the consuming library. |

## Dependency relationships

| Relationship | Description |
| --- | --- |
| `webkernel/codebase` → `webkernel/lifecycle` | Codebase requires Lifecycle, so Webkernel applications receive it transitively. |
| Standalone project → `webkernel/lifecycle` | Add it to your own `require` section. |
| Lifecycle ↔ workers | Built-in workers live in the same package (`src/Workers/`). There is no separate workers package. |

## Package layout

| Item | Location |
| --- | --- |
| PSR-4 root | `Webkernel\Lifecycle\` → `src/` |
| Built-in workers | `src/Workers/` (`Webkernel\Lifecycle\Workers\`) |
| Declaration schema | `support/schemas/extra-webkernel.v1.json` |
| Global helpers (autoload `files`) | `load.lifecycle.functions.php` |
| Plugin class (`extra.class`) | `Webkernel\Lifecycle\Plugin\LCPlugin` |

The engine, the workers and their Composer declarations ship and autoload together.

## Check your environment

```sh
php --version
composer --version
composer show --platform
```

The plugin API has its own version; do not assume it matches the Composer executable version. The package does not require `composer/composer` as an application runtime dependency. Composer supplies its integration classes while running commands; application helper code uses the generated Composer runtime metadata.

## Next step

Continue with [Installation](03-installation.en.md).
