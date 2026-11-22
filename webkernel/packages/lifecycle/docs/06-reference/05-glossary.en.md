# Glossary

| Term | Meaning here |
| --- | --- |
| Root package | The Composer project consuming Lifecycle. Its installation path is the application base path. |
| Installed package | A dependency recorded in Composer's local installed repository. |
| Declaration | Supported metadata under `extra.webkernel` in a package manifest. |
| Composer-time | Execution during Composer commands, such as install, update or autoload dump. |
| Runtime | Execution during application requests, commands or long-running processes after autoload. |
| Listener / callback | An invokable class or public class-method target attached to a supported script event. |
| Check | A parsed PHP-version failure condition with a severity and message. |
| Action | An instantiable implementation of `LifecycleActionContract`, normally run after autoload generation. |
| Worker | A built-in event listener or example action shipped in `src/Workers/`. |
| Plugin API | Composer's integration boundary for plugins; required through `composer-plugin-api`. |
| Runtime API | Composer-generated utilities available with the application's autoloader. |
| PSR-4 prefix | A Composer namespace prefix mapped to source directories. Used for class loading and package-local references. |
| Path repository | A Composer repository pointing to a local package directory; it can install through a symlink. |
| Canonical path | A path resolved with `realpath()`, following symlinks and existing filesystem structure. |
| Idempotent action | Work that can run again without producing unintended additional effects. |
| Atomic replacement | Publishing a complete generated file through replacement, so readers do not observe a partial write. |

Return to the [reading path](../../README.md).
