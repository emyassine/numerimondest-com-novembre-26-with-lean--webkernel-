# Composer Output

`StandardComposerIO` sends listener and action output through the current Composer IO. Lifecycle binds it during activation and script-event handling, then restores the previous binding in `finally`.

## Write a message

Inside a listener or action:

```php
StandardComposerIO::write('Build manifest is current.');
StandardComposerIO::error('Unable to publish the build manifest.');
```

Import `Webkernel\Lifecycle\StandardComposerIO` in the class. Both methods accept one string or a list of strings. Outside an output tree, `error()` uses Composer's error output; inside a tree it adds a red message to that tree. Printing an error does not itself throw or mark an action failed: throw an exception when execution must fail.

## Group related work

```php
StandardComposerIO::branch('Build files', static function (): void {
    StandardComposerIO::result('DONE', 'Manifest generated.');
    StandardComposerIO::write('Wrote runtime/build/manifest.json.');
}, 'cyan');
```

Lifecycle already opens the event's output tree. Add branches inside it; do not call `tree()` from a listener or action, because nested trees throw `LogicException`. The helper calculates branch connectors and indentation centrally.

## Statuses and timing

| Status | Style |
| --- | --- |
| `PASS`, `DONE` | Green. |
| `WARN` | Yellow. |
| `FAIL`, `BLOCKED` | Red. |
| `CHECK`, `RUN`, `START` | Cyan. |
| Other labels, including `INFO` and `SKIP` | Gray. |

`result($status, $message, $duration)` accepts an optional formatted duration. `duration($nanoseconds)` formats a non-negative integer in microseconds, milliseconds, seconds or minutes. Use `hrtime(true)` differences when measuring work. These displayed durations describe the measured phase, not a request-performance guarantee.

`line()` and `blankLine()` add tree entries. `write()` and `error()` also work with bound IO outside a tree. Calling them without bound IO throws `LogicException`; they are Composer callback APIs rather than general application logging functions.

## Color and verbosity

Composer IO controls terminal decoration and verbosity. Status words remain readable with `--no-ansi`. The helper does not expose a per-message verbosity argument; use the event's underlying IO when you need a Composer-specific interaction or verbosity level.

## Next step

Use the [Public API reference](../06-reference/04-public-api.en.md) for signatures, or continue with [Path Helpers](../04-path-helpers/01-overview.en.md).
