# Troubleshooting

## Composer reports that the plugin is blocked

**Cause:** Composer requires explicit permission to activate plugins.
**Fix:** add `webkernel/lifecycle: true` under the root project's `config.allow-plugins`.

## A lifecycle class cannot be found

**Check:** the class has the expected PSR-4 namespace and path.
**Fix:** regenerate the autoloader. For path repositories, confirm the symlink points to the source package and that the current PSR-4 mapping is reflected in Composer's metadata.

## Composer rejects a declaration

Use the package name and declaration path in the error to locate the invalid `extra.webkernel` value. Look for:

- unknown keys;
- unsupported event names;
- empty callback arrays;
- malformed class/method targets;
- invalid check severity;
- an action not implementing `LifecycleActionContract`.

## A version check blocks an install or update

Read the declared message and severity: `danger` deliberately stops the operation. `fix` is printed as guidance only. Make sure the PHP binary used for Composer is the intended one; it may differ from the web-serving binary.

## An action fails during autoload generation

Inspect Composer output for the action class and exception. The runner continues with later actions, then fails the phase. Verify the action is idempotent, has no required constructor arguments, writes to a valid destination and checks filesystem results.

## A project path is stale after moving the application

Run `composer dump-autoload` from the application project. Composer regenerates root package metadata and the post-autoload worker refreshes `BASE_PATH`. Resolution does not use `getcwd()`.

## An installed package is outside `vendor/`

Check the package type and `LCPackageType` destination rules: the custom installer intentionally places selected types in framework directories. Use `Composer\InstalledVersions::getInstallPath()` or `webkernel_package()` rather than building a vendor path.

The [current destination table](05-advanced-usage/01-custom-installer.en.md) uses `vendor_path()` as its module/FFI base. Do not assume an application-level `modules/` directory is the installed location.

## No callback output appears

**Check:** the plugin is allowed, installed and active, the event name is supported, and the callback class is autoloadable. `--no-plugins` disables plugin execution; `--no-scripts` suppresses script-event work. A local plugin also cannot receive an event fired before it was installed.
**Fix:** run `composer dump-autoload` with the plugin and script events enabled, then confirm the declaration is under `extra.webkernel.lifecycle.events` or `actions` as appropriate.

## A helper behaves like the host framework's helper

**Cause:** Lifecycle only defines missing function names. A framework may already own `base_path()` or `vendor_path()`.
**Fix:** check which implementation is loaded. Existing helpers keep their own behavior and performance; the Lifecycle helper reference applies when Lifecycle owns that function name.

## `vendor_path()` points to another Composer project

**Cause:** the process registered more than one Composer loader. The helper caches the first registered-loader map entry when first called.
**Fix:** inspect the project's autoloader setup. Use package-aware metadata for package paths; do not treat this helper as a per-caller loader selector.

## `webkernel_package()` cannot resolve a package

**Check:** pass the short name, such as `codebase`, not `webkernel/codebase`. Confirm Composer records the package and its directory exists. Metapackages may have no install path.
**Fix:** refresh the package through Composer when installation metadata is stale. The helper requires a real directory and caches successful lookups for the process; restart a long-running process if the installed tree changed after its cache was populated.

## An editor cannot load the declaration schema

**Cause:** the schema hint points to an unavailable location, or the editor has no association for the nested `extra.webkernel` object.
**Fix:** inspect the local path or editor association. The bundled schema describes that object, not the full Composer manifest. Lifecycle's runtime declaration validation is independent of remote editor downloads.

## Root callbacks do not run first

**Cause:** discovering the root declaration first does not determine dispatch order. Event callbacks prioritize `webkernel/codebase`, then sort remaining package names. A named root participates in that sorting.
**Fix:** keep ordered dependent steps in one callback list or one task. See [Supported Events](06-reference/02-supported-events.en.md) for the event, action and check orders.

## `StandardComposerIO` reports missing IO or a nested tree

**Cause:** the helper is being used outside bound Composer context, or a listener tried to open another event tree.
**Fix:** use it inside dispatched listeners/actions and add `branch()` entries to the existing tree. A manual integration must bind and restore Composer IO; use the application's own logger for request logging.

## Return to the guides

See [Declaring Metadata](03-guides/01-declaring-metadata.en.md), [Path Helpers](04-path-helpers/01-overview.en.md) or the [reading path](../README.md).
