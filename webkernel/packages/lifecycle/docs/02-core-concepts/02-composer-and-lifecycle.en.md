# Composer and Lifecycle

Composer owns the dependency graph. Lifecycle adds a declaration format and execution rules inside that workflow.

## Responsibility map

| Concern | Owner |
| --- | --- |
| Dependency resolution, downloads and lock-file updates | Composer. |
| Package installation, updates and removal | Composer, using registered installers. |
| Generated autoload files and installed-package metadata | Composer. |
| Permission to activate a plugin | The root project's `config.allow-plugins`. |
| `extra.webkernel` validation and callback dispatch | Lifecycle. |
| Supported Webkernel install destinations | Lifecycle's installer. |
| Application-specific generated content | The package declaring the action. |

## Why declarations live in package manifests

A package can describe the work it needs under `extra.webkernel.lifecycle`. Lifecycle gathers these declarations across the installed package set. You do not need to copy every dependency's callbacks into the root project's `scripts` section.

Composer's native `scripts` execution is rooted in the consuming project. Lifecycle explicitly reads dependency declarations through its plugin. Both mechanisms can coexist. See Composer's [script documentation](https://getcomposer.org/doc/articles/scripts.md) for the native event model.

## Metadata changes

| Change | What to do |
| --- | --- |
| A lifecycle declaration in an installed symlinked path package | Run the relevant Composer command again. Its current installed manifest is read for declarations. |
| `require`, versions or other dependency metadata | Update the affected package through Composer so the lock and installed metadata agree. |
| PSR-4 prefixes, class paths or `autoload.files` | Refresh the package metadata as needed, then regenerate the consuming project's autoloader. |
| An application runtime configuration file | Follow the application's own configuration/cache workflow. |

Reading a live manifest for lifecycle declarations does not turn Composer dependency metadata into a live configuration system. Class-reference prefixes come from Composer's package autoload metadata.

## Plugin activation

Activation enables optimized and APCu autoloader settings in the current Composer configuration, resolves the project base path and registers the installer. Composer generates the maps; APCu support depends on the PHP process. These activation settings do not install extensions or rewrite the application's `composer.json`.

For plugin permission, see Composer's [allow-plugins configuration](https://getcomposer.org/doc/06-config.md#allow-plugins).

## Next step

Read [Composer-time and Runtime](03-composer-time-and-runtime.en.md) before choosing where to put a task.
