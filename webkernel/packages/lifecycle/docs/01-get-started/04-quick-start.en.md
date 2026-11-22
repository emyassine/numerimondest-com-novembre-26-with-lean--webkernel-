# Quick Start

This page creates a package-level hook that runs after Composer generates the autoloader.

## 1. Write an action

With the PSR-4 mapping in the next step, save this class as `src/GenerateManifest.php` in the consuming project:

```php
<?php declare(strict_types=1);

namespace Acme\Build;

use Webkernel\Lifecycle\StandardComposerIO;
use Webkernel\Lifecycle\StandardLCAction;

final class GenerateManifest extends StandardLCAction
{
    protected function run(): void
    {
        StandardComposerIO::write('Manifest action is ready.');
    }
}
```

## 2. Declare it

In your package's `composer.json`:

```json
{
  "autoload": { "psr-4": { "Acme\\Build\\": "src/" } },
  "extra": {
    "webkernel": {
      "lifecycle": {
        "actions": ["Acme\\Build\\GenerateManifest"]
      }
    }
  }
}
```

## 3. Run it

```sh
composer dump-autoload
```

Lifecycle validates the declaration, runs checks, then your action, and reports the result through Composer's output.

This first action only writes a message. For a complete action that publishes a generated artifact, see [Writing Actions](../03-guides/05-writing-actions.en.md). You do not need to register a built-in worker yourself; Lifecycle's manifest already wires the shared action phase.

## 4. Add a check (optional)

Add this list under the same `extra.webkernel.lifecycle` object:

```json
{
  "checks": [
    {
      "check": "PHP_VERSION_ID < 80500",
      "on-fail": { "type": "danger", "message": "PHP 8.5 or newer is required." },
      "fix": "Install PHP 8.5 or newer for the Composer process."
    }
  ]
}
```

## Where to go next

- Understand the design: [Architecture](../02-core-concepts/01-architecture.en.md).
- Go deeper on declarations: [Declaring Metadata](../03-guides/01-declaring-metadata.en.md).
