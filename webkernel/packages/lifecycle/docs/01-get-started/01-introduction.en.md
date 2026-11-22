# Introduction

`webkernel/lifecycle` is a Composer plugin (`type: composer-plugin`) and a library of lifecycle APIs. It can be installed in any PHP application that uses Composer, including applications built with Laravel or another framework.

## What it does

| Capability | Description |
| --- | --- |
| Plugin activation | Hooks into Composer, subscribes to script and package events. |
| Declarations | Reads and validates Webkernel metadata from package manifests (`extra.webkernel`). |
| Event dispatch | Runs declared callbacks on supported Composer script events. |
| Environment checks | Evaluates constrained PHP-version expressions and reports or blocks. |
| Actions | Runs declared action classes after autoload generation. |
| Custom installer | Places Webkernel-specific package types in an explicit directory layout. |
| Extension APIs | `StandardLCEvent`, `StandardLCAction` and `StandardComposerIO`. |
| Path helpers | Global `base_path()` and `vendor_path()` (framework-agnostic) and `webkernel_package()` (Webkernel-specific). |

## What it does not do

- It does not replace Composer's dependency solving, downloads, lock file or autoload generation.
- It does not replace Composer scripts or other plugins; it adds callbacks to Composer's event flow.
- It does not scan modules or initialize providers on each HTTP request.
- It does not install or enable PHP extensions (such as APCu).
- It never executes PHP found in package metadata, nor the `fix` text of a check.

## When to use it

- You build on Webkernel: Lifecycle is received through `webkernel/codebase`.
- You want declarative, validated Composer-time hooks across several packages.
- You want path helpers that do not depend on `getcwd()` or on a hardcoded `vendor/` depth.

## How to approach it

Start with one task. If it belongs to a Composer phase, write a listener. If it generates an artifact after autoload generation, write an action. If it only needs an application path, load Composer and call a helper. You do not need to introduce an application container or provider to use these APIs.

Existing global helper functions keep their own implementation. In another framework, check whether it already provides `base_path()` before expecting Lifecycle's helper behavior.

## Next step

Check the [Requirements and Dependencies](02-requirements-and-dependencies.en.md), then [install the package](03-installation.en.md).
