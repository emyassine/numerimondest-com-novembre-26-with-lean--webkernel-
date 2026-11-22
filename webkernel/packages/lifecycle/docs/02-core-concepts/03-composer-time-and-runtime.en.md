# Composer-time and Runtime

Lifecycle has two distinct uses: prepare work during Composer commands, and expose path helpers after Composer autoload. Choosing the right phase keeps runtime work predictable.

## What runs when

| Phase | Work |
| --- | --- |
| Composer plugin activation | Installer registration, path injection and autoloader configuration. |
| Supported script event | Declaration reading, validation and callback dispatch. |
| Built-in post-autoload callback | Base-path refresh, checks and declared actions. |
| Application autoload | Global helper definitions and initial base-path injection. |
| Application helper call | A prepared path read, a cached vendor lookup, or a cached package lookup. |

The plugin and workers use Composer classes supplied by the Composer process. Including an application's generated autoloader does not make Composer's full plugin API available to an HTTP request.

## Prepare once, consume explicitly

Generate manifests or other derived state after autoload generation, then let the application read those outputs. A request should not enumerate providers, inspect every package manifest or invoke the lifecycle dispatcher to discover application behavior.

Actions can run repeatedly. Make them deterministic and safe to repeat. In Webkernel, generated application files belong under `runtime/`; persistent application data belongs under `storage/`. Lifecycle does not automatically select an output location for a custom action.

## Helpers in another framework

`base_path()` and `vendor_path()` use Composer metadata and autoloader state. They do not require a Webkernel container. `webkernel_package()` specifically resolves `webkernel/*` names.

Each helper is defined only if a function with that name does not already exist. If another framework has already defined `base_path()`, that function keeps its own behavior. Installing Lifecycle does not replace an existing framework helper; verify which implementation owns the name when comparing behavior or performance.

## First-install availability

An event can only reach the plugin after Composer has loaded and activated it. Do not depend on a newly installed local plugin observing events that occurred before its installation, particularly pre-install/update phases or `post-root-package-install` during project creation. Use Composer's platform requirements for mandatory PHP constraints and post-autoload work for tasks that need newly installed classes.

## Next step

Start [Declaring Metadata](../03-guides/01-declaring-metadata.en.md), or go directly to [Path Helpers](../04-path-helpers/01-overview.en.md).
