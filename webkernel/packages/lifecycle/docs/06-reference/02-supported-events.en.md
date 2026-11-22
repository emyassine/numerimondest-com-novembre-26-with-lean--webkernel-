# Supported Events

`LCEventTarget` defines the script event names accepted under `extra.webkernel.lifecycle.events`. A string or a non-empty callback list is required for every declared event.

## Script events

| Event name | Phase |
| --- | --- |
| `pre-install-cmd` | Before install with a lock file. |
| `post-install-cmd` | After install with a lock file. |
| `pre-update-cmd` | Before update, including install without a lock file. |
| `post-update-cmd` | After update, including install without a lock file. |
| `pre-autoload-dump` | Before autoloader generation. |
| `post-autoload-dump` | After autoloader generation. |
| `post-root-package-install` | Root installation during project creation, before dependency installation. |
| `post-create-project-cmd` | After project creation. |

Events only reach Lifecycle when its plugin is loaded and active. In particular, a project-local plugin cannot observe a creation or pre-install event that occurred before the plugin was available. See Composer's [event documentation](https://getcomposer.org/doc/articles/scripts.md#event-names) for event timing.

Script callbacks receive `Composer\Script\Event` when their target declares a parameter. `StandardLCEvent` wraps this object for `run()`. The `LCEventTarget` enum's string value and `extraKey()` result are the exact manifest event name.

## Package operation subscriptions

The plugin also subscribes to these package operation events:

| Before | After |
| --- | --- |
| `pre-package-install` | `post-package-install` |
| `pre-package-update` | `post-package-update` |
| `pre-package-uninstall` | `post-package-uninstall` |

These subscriptions invalidate cached declarations and report recognized Webkernel package operations. They are internal subscriptions, not accepted keys in the lifecycle `events` object. Their Composer event type is `Composer\Installer\PackageEvent`.

## Ordering

| Phase | Current order |
| --- | --- |
| Event callbacks | `webkernel/codebase`, then other package names alphabetically; list order within each package. |
| Checks | Root discovery first, then Composer's installed repository order; list order within each package. |
| Actions | Empty-name entry if present, then `webkernel/codebase`, then other names alphabetically; list order within each package. |

A named root package is included by name in event/action sorting. The declaration format has no priority or dependency-order field. Declaring the same work in multiple places can invoke it multiple times.

## Related guide

See [Composer Events](../03-guides/02-composer-events.en.md) for callback forms and invocation rules.
