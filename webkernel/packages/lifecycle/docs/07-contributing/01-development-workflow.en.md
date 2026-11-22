# Development Workflow

This section is for changes to Lifecycle itself. For application or module tasks, start with the [Guides](../03-guides/01-declaring-metadata.en.md).

## Work from the package boundary

| Change | Relevant source |
| --- | --- |
| Declaration grammar | `src/Declaration/` and `support/schemas/extra-webkernel.v1.json`. |
| Callback forms or order | `src/Event/`. |
| Check evaluation | `src/Check/` and built-in worker declarations. |
| Action contract or execution | `src/Action/`, `src/Contracts/`, `StandardLCAction`. |
| Helper initialization or paths | `src/Path/`, `load.lifecycle.functions.php`. |
| Package destinations | `src/Install/`. |
| Output behavior | `StandardComposerIO`. |
| Activation and Composer event adapters | `src/Plugin/`. |

Read the current neighboring files before editing. Keep changes focused, use the repository's PHP naming/header conventions and keep runtime code independent of a source checkout's absolute location.

## Consume through Composer

The repository application is `webkernel/`; its path repositories point at framework sources with symlinks. Run Composer from that consuming project when refreshing installed metadata and autoload maps:

```sh
cd webkernel
composer dump-autoload
```

For changed dependency/autoload package metadata, update the relevant path package as needed before dumping:

```sh
composer update webkernel/lifecycle
```

Do not hand-edit the generated autoloader or installed-package files. A live lifecycle declaration in a symlinked manifest can be reread on the next command; dependency and PSR-4 metadata still follow Composer's package-update workflow.

## Verify the changed behavior

| Change | Useful check |
| --- | --- |
| Documentation | Resolve relative links and check JSON/PHP sample syntax. |
| Manifest | `composer validate` in the consuming project. |
| Listener/action | Run the event that reaches it; verify failure as well as successful output. |
| Installer | Exercise the affected package type through Composer and inspect the actual install path. |
| Helper | Load the consuming project's autoloader; inspect root, custom vendor and package path behavior. |
| Performance | Record PHP, OPcache, cache state and measurement scope. |

Manifest validation alone does not prove a callback autoloads. Avoid running generation actions against persistent application data merely to inspect output. Use a disposable fixture project for installation or destructive-failure scenarios.

## Keep contracts and documentation together

When a declaration contract changes, update both the PHP validator and editor schema, then the guide and reference. When an install destination changes, update its table from the actual templates. Keep the built-in worker inventory aligned with the manifest's callback order.

The package's benchmark script accepts the Composer autoloader path as its first argument; read [Performance](../04-path-helpers/05-performance.en.md) before using it in a custom layout. Document measured conditions and avoid turning a local sample into a general speed guarantee.

## Documentation conventions

Section directories use `01-name/`; pages use `01-name.en.md` for English, with the language code in the same position for other languages. Keep the package README as the reading-path index. Add relative links and a useful next step when a page belongs to a sequence.

Return to [Troubleshooting](../08-troubleshooting.en.md) for symptoms in a consuming application.
