# Writing Actions

An action owns one piece of Composer-time work, usually a generated artifact. Declare it once; the built-in post-autoload worker runs it after environment checks whenever Composer regenerates the autoloader.

## 1. Write the action

For `Acme\Build\` mapped to `src/`, save this as `src/GenerateManifest.php`:

```php
<?php declare(strict_types=1);

namespace Acme\Build;

use RuntimeException;
use Webkernel\Lifecycle\StandardComposerIO;
use Webkernel\Lifecycle\StandardLCAction;

final class GenerateManifest extends StandardLCAction
{
    protected function run(): void
    {
        $directory = base_path('runtime/build');

        if (! is_dir($directory)
            && ! mkdir($directory, 0775, true)
            && ! is_dir($directory)) {
            throw new RuntimeException('Unable to create the build directory.');
        }

        $output_path = $directory . '/manifest.json';
        $contents = json_encode(['format' => 1], JSON_THROW_ON_ERROR) . "\n";

        $current_contents = is_file($output_path) ? file_get_contents($output_path) : false;
        if ($current_contents === $contents) {
            StandardComposerIO::write('Build manifest is current.');
            return;
        }

        $temporary_path = tempnam($directory, '.manifest-');
        if ($temporary_path === false) {
            throw new RuntimeException('Unable to create a temporary manifest.');
        }

        try {
            if (file_put_contents($temporary_path, $contents) !== strlen($contents)
                || ! rename($temporary_path, $output_path)) {
                throw new RuntimeException('Unable to publish the build manifest.');
            }
        } finally {
            if (is_file($temporary_path)) {
                unlink($temporary_path);
            }
        }

        StandardComposerIO::write('Wrote runtime/build/manifest.json.');
    }
}
```

This example stores generated application state under `runtime/`, avoids rewriting an unchanged artifact and publishes a changed file by replacing it in the same directory. For generated PHP, invalidate the affected OPcache entry after replacement when applicable.

## 2. Declare it

```json
{
  "extra": {
    "webkernel": {
      "lifecycle": {
        "actions": ["Acme\\Build\\GenerateManifest"]
      }
    }
  }
}
```

Run `composer dump-autoload`. Actions are classes, not `ClassName::method` targets. They must be instantiable, implement `LifecycleActionContract` and have no required constructor arguments. Lifecycle validates every declared action before starting the action phase.

## Keys, names and context

`StandardLCAction` provides `key()`, `name()` and a final `handle(Event $event)` method. You implement `protected function run(): void`. Use `composerEvent()` inside the action if you need the Composer event.

| Class | Default key | Default name |
| --- | --- | --- |
| `GenerateManifest` | `generate-manifest` | `Generate Manifest` |
| `GenerateManifestAction` | `generate-manifest` | `Generate Manifest` |

Override `key()` or `name()` for a stable identifier or custom label. The current runner executes declarations as listed; a key does not deduplicate actions or create dependency ordering. Implement `LifecycleActionContract` directly when the standard base does not fit.

## Order and failure

Action lists retain their order within a package. The runner prioritizes an empty package name, then `webkernel/codebase`, then other names alphabetically. A named root package participates by name; do not assume it always runs first.

An execution exception is reported, then later actions continue. The built-in worker throws after the phase if any actions failed. A declaration-validation error stops before action execution. Successful writes are not rolled back if a later action fails.

## Optional example action

Lifecycle includes `Webkernel\Lifecycle\Workers\ActionExampleAction`, which is disabled by default. Declaring it writes `generated/lifecycle-example.txt` inside the installed Lifecycle package. It demonstrates the API; the application-oriented example above writes to application runtime storage instead.

## Next step

Format output with [Composer Output](06-composer-output.en.md). See [Failure Semantics](../05-advanced-usage/04-failure-semantics.en.md) for phase-level behavior.
