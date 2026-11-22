# Public API

The usual extension surface consists of the three standard classes, the action contract, path helpers and declarations. Composer's `Event` below means `Composer\Script\Event`; `IOInterface` means `Composer\IO\IOInterface`.

## `StandardLCEvent`

Namespace: `Webkernel\Lifecycle`.

| Method | Visibility | Purpose |
| --- | --- | --- |
| `__invoke(Event $event): void` | Final public. | Stores the event and runs the listener. |
| `composerEvent(): Event` | Final protected. | Returns invocation context; throws before an event is supplied. |
| `run(): void` | Abstract protected. | Implement the listener's work. |

## `StandardLCAction`

Namespace: `Webkernel\Lifecycle`. Implements `Contracts\LifecycleActionContract`.

| Method | Visibility | Purpose |
| --- | --- | --- |
| `key(): string` | Public, overridable. | Derived kebab-case key. |
| `name(): string` | Public, overridable. | Derived display name. |
| `handle(Event $event): void` | Final public. | Stores the event and runs the action. |
| `composerEvent(): Event` | Final protected. | Returns invocation context; throws before an event is supplied. |
| `run(): void` | Abstract protected. | Implement the action's work. |

`LifecycleActionContract` requires `key()`, `name()` and `handle(Event $event)`. Direct implementations provide all three public methods. Keys do not cause deduplication in the current runner.

## `StandardComposerIO`

Namespace: `Webkernel\Lifecycle`. All methods are static.

| Method | Purpose |
| --- | --- |
| `write(string\|array $messages): void` | Writes messages; arrays are lists of strings. |
| `error(string\|array $messages): void` | Writes error messages; formatting does not throw automatically. |
| `branch(string $message, callable $draw_children, string $color = 'default'): mixed` | Groups nested tree output and returns the callback result. |
| `line(string $message): void` | Adds a tree line. |
| `result(string $status, string $message, ?string $duration = null): void` | Adds a styled status line. |
| `blankLine(bool $continue_current_branch = false): void` | Adds a blank tree entry. |
| `duration(int $nanoseconds): string` | Formats a non-negative duration; negative input throws `LogicException`. |
| `tree(string $event_name, callable $draw_children, ?callable $completion = null): mixed` | Owns an output tree; nesting throws `LogicException`. |
| `bind(IOInterface $io): ?IOInterface` | Sets IO and returns the previous binding. |
| `restore(?IOInterface $io): void` | Restores a previous binding. |

In ordinary listeners/actions, the plugin owns binding and tree lifetime. Use `write()`, `error()` and branches inside that scope. Manual integrations must restore a binding in `finally`. See [Composer Output](../03-guides/06-composer-output.en.md).

## Global functions

| Signature | Scope |
| --- | --- |
| `base_path(string $path = ''): string` | Composer root path. |
| `vendor_path(string $path = ''): string` | First registered Composer loader's directory, cached on first use. |
| `webkernel_package(string $package, ?string $sub_path = null): string` | Canonical installed path for `webkernel/<package>`, cached per package. |

Functions are defined only when their names are available. See the [Path Helpers](../04-path-helpers/01-overview.en.md) for exact return, error and cache behavior.

## Engine and Composer integration

These public entry points support internal orchestration. Extend the standard APIs for ordinary package tasks; direct use requires the appropriate Composer context and bound IO where output is emitted.

| Component | Entry points |
| --- | --- |
| `Declaration\LCDeclarationReader` | `read(Composer $composer): array`, static `forget(Composer $composer): void`. |
| `Declaration\LCDeclarationValidator` | `validate(string $package_name, string $package_type, array $webkernel_extra): void`. |
| `Declaration\LCClassReferenceResolver` | `resolve(string $reference, array $psr4_prefixes, string $package_name): string`. |
| `Event\LCEventDispatcher` | `dispatch(string $event_name, object $event, array $packages, int $started_at): void`. |
| `Event\LCEventTarget` | Backed enum; `extraKey(): string`. |
| `Check\LCCheckRunner` | `run(Event $event): void`. |
| `Action\LCActionRunner` | `run(Event $event): int`, returning failed action count. |
| `Path\LCBasePathInjector` | Static `inject(?Composer $composer = null): void`. |
| `Install\LCBaseInstaller` | `supports(string $packageType): bool`, `getInstallPath(PackageInterface $package): string`. |
| `Install\LCInstallerLocations` | `types(): array`, `destination(PackageInterface $package): ?string`, `parentModule(PackageInterface $package): ?array`. |
| `Install\LCPackageType` | Backed enum; `description(): string`, `requiresParentModule(): bool`. |
| `Plugin\LCPlugin` | Composer plugin lifecycle, `getSubscribedEvents()`, `onScriptEvent()`, `onPackageEvent()`. |
| Plugin activation and event adapters | `LCPluginActivator::activate()`, `LCPluginScriptEventHandler::handle()`, `LCPackageOperationEventHandler::handle()`. |

Composer supplies its plugin interfaces and event classes while running Composer. These classes are not a substitute for the application's runtime container or HTTP lifecycle.
