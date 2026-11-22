# Architecture

Lifecycle connects package declarations to Composer events. The package that owns a task declares it, Composer supplies the event, and Lifecycle validates and invokes the task.

## The execution path

| Step | Component | Responsibility |
| --- | --- | --- |
| 1 | `LCPlugin` | Activates the plugin and subscribes to supported script and package events. |
| 2 | `LCDeclarationReader` | Collects root and installed package metadata. |
| 3 | `LCDeclarationValidator` | Validates `extra.webkernel` before dispatch. |
| 4 | `LCEventDispatcher` | Resolves and validates the callbacks for the current event, then invokes them. |
| 5 | Built-in workers | Refresh paths, run checks and run actions at their declared phases. |
| 6 | `StandardComposerIO` | Groups output, statuses and timings through Composer IO. |

`LCBaseInstaller` participates separately in Composer's installation manager. It resolves paths for supported Webkernel package types; it is not an event listener declared in `extra.webkernel.lifecycle.events`.

## One package, focused responsibilities

| Directory | Purpose |
| --- | --- |
| `src/Plugin/` | Composer activation and event adapters. |
| `src/Declaration/` | Reading, validation and class-reference resolution. |
| `src/Event/` | Supported script events and callback dispatch. |
| `src/Check/`, `src/Action/` | Check evaluation and action execution. |
| `src/Workers/` | Built-in callbacks and the optional example action. |
| `src/Install/` | Supported package types and destination resolution. |
| `src/Path/` | Composer root path injection. |

The extension APIs live at the namespace root: `StandardLCEvent`, `StandardLCAction` and `StandardComposerIO`. `Contracts/LifecycleActionContract` defines the action boundary. You normally extend these APIs rather than instantiate the engine classes yourself.

## How to think about a task

- **It belongs to a particular Composer phase:** declare an event listener.
- **It generates one artifact after autoload generation:** declare an action.
- **It expresses a supported PHP-version failure condition:** declare a check.
- **It only needs a project or dependency path:** call a path helper after Composer autoload.

Keep behavior with the package that owns it. The application declares application work; a library or module declares its own work. Built-in workers already run the shared checks and actions, so a package only needs to add its declarations.

## Next step

Read [Composer and Lifecycle](02-composer-and-lifecycle.en.md) to understand who owns each part of the workflow.
