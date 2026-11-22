# Built-in Workers

Workers are the event listeners and actions shipped inside Lifecycle. Their implementations live in `src/Workers/`; their active hooks are declared in Lifecycle's own `composer.json`.

## Inventory

All classes below use the `Webkernel\Lifecycle\Workers\` namespace.

| Class | Declared hook | Work |
| --- | --- | --- |
| `EventDependencyCheck` | `pre-install-cmd`, `pre-update-cmd` | Runs the collected environment checks. |
| `EventSetBasePath` | First Lifecycle callback for `post-autoload-dump`. | Refreshes the Composer root path. |
| `EventPostAutoloadDump` | Second Lifecycle callback for `post-autoload-dump`. | Runs checks, then declared actions. |
| `ActionExampleAction` | No default action declaration. | Writes a demonstration artifact when explicitly declared. |

## Checks and actions are already wired

Declaring `checks` or `actions` in your package is sufficient. The built-in workers collect them across root and installed packages. Do not also declare the built-in worker classes under your own events unless you deliberately want another invocation of the same global phase.

Lifecycle itself declares a blocking `PHP_VERSION_ID < 80500` check. Its optional fix text is displayed; no fix command is executed. The package also declares PHP 8.5 as a Composer requirement.

## Base-path refresh

`EventSetBasePath` remembers the previous `BASE_PATH`, calls `LCBasePathInjector` with the Composer instance and reports a change only if the resolved value differs. This keeps the injected root aligned with regenerated Composer installation metadata.

Its place before `EventPostAutoloadDump` is an order inside Lifecycle's callback list. It does not imply that Lifecycle's whole package runs before every other package. See [Composer Events](../03-guides/02-composer-events.en.md) for cross-package order.

## Post-autoload phase

The worker opens an environment-check branch, runs checks, then opens a declared-actions branch and runs actions. A blocking check prevents actions. Action execution failures are collected and reported; once the action runner returns, the worker throws if the failure count is nonzero.

## Optional example

Add `Webkernel\Lifecycle\Workers\ActionExampleAction` to `extra.webkernel.lifecycle.actions` to opt in, escaping backslashes in JSON. It writes `generated/lifecycle-example.txt` under the installed Lifecycle package, creating the directory when needed. It checks directory and write failures and reports the artifact path. It is a demonstration; application-generated state should go to the application's owned runtime location.

## Next step

Read [Failure Semantics](04-failure-semantics.en.md) to understand which failures continue and which stop Composer.
