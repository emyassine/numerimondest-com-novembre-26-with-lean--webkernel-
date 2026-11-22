# Webkernel Lifecycle — Documentation

`webkernel/lifecycle` is a Composer plugin and a library of lifecycle APIs for PHP applications. It validates declarative package metadata, runs checks and actions during Composer events, resolves project paths reliably, and installs Webkernel packages into an explicit directory layout.

This documentation is organized as a progressive path: start at the top, and go deeper only when you need to.

## Reading path

| Step | Section | You will learn |
| --- | --- | --- |
| 1 | [Get Started](docs/01-get-started/01-introduction.en.md) | What Lifecycle is, its requirements and dependencies, how to install it and run a first hook. |
| 2 | [Core Concepts](docs/02-core-concepts/01-architecture.en.md) | The architecture, what Composer owns vs. what Lifecycle owns, and the Composer-time/runtime boundary. |
| 3 | [Guides](docs/03-guides/01-declaring-metadata.en.md) | How to declare metadata, subscribe to events, write listeners, define checks and write actions. |
| 4 | [Path Helpers](docs/04-path-helpers/01-overview.en.md) | `base_path()`, `vendor_path()` and `webkernel_package()`: when to use which, and how they behave. |
| 5 | [Advanced Usage](docs/05-advanced-usage/01-custom-installer.en.md) | Custom installer, Composer runtime metadata, built-in workers and failure semantics. |
| 6 | [Reference](docs/06-reference/01-declaration-schema.en.md) | Declaration schema, supported events and checks, public API and glossary. |
| 7 | [Contributing](docs/07-contributing/01-development-workflow.en.md) | Development workflow for working on Lifecycle itself. |
| 8 | [Troubleshooting](docs/08-troubleshooting.en.md) | Symptoms, causes and fixes. |

## Quick orientation

- **New to Lifecycle?** Read [Introduction](docs/01-get-started/01-introduction.en.md), then [Installation](docs/01-get-started/03-installation.en.md).
- **Want to run code on Composer events?** Jump to [Composer Events](docs/03-guides/02-composer-events.en.md).
- **Need reliable paths in your app?** Go to [Path Helpers](docs/04-path-helpers/01-overview.en.md).
- **Something is failing?** See [Troubleshooting](docs/08-troubleshooting.en.md).

## Conventions

- Code samples use `declare(strict_types=1)`.
- "Composer-time" means during Composer commands (install, update, dump-autoload).
- "Runtime" means during your application's requests or processes.
- Paths in this documentation are relative to the Lifecycle package unless stated otherwise.
