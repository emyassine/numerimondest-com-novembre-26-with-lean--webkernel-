# Writing Listeners

Extend `StandardLCEvent` when you want a class callback with a small `run()` method. The base class adapts Composer's event to your implementation.

## Standard listener

For the PSR-4 prefix `Acme\Build\` mapped to `src/`, save this as `src/AfterAutoloadDump.php`:

```php
<?php declare(strict_types=1);

namespace Acme\Build;

use Webkernel\Lifecycle\StandardComposerIO;
use Webkernel\Lifecycle\StandardLCEvent;

final class AfterAutoloadDump extends StandardLCEvent
{
    protected function run(): void
    {
        StandardComposerIO::write('Autoload dump callback completed.');
    }
}
```

Declare the class under the chosen [Composer event](02-composer-events.en.md). Lifecycle calls the base class's final `__invoke()` method, which stores the Composer event and invokes `run()`.

## Access the Composer event

Inside `run()`, `$this->composerEvent()` gives access to `Composer\Script\Event`:

```php
<?php declare(strict_types=1);

namespace Acme\Build;

use Webkernel\Lifecycle\StandardComposerIO;
use Webkernel\Lifecycle\StandardLCEvent;

final class ReportEvent extends StandardLCEvent
{
    protected function run(): void
    {
        $event = $this->composerEvent();
        StandardComposerIO::write('Handling ' . $event->getName());
    }
}
```

Use the event to access Composer or its IO when the higher-level APIs are insufficient. Before an invocation has supplied an event, `composerEvent()` throws `LogicException`. Treat it as callback context rather than long-lived application state.

## Existing classes and method callbacks

The standard base is optional. A public `__invoke()` method, or a public method named through `ClassName::method`, also works. Static methods do not need an instance; instance methods require a class constructible without required arguments. Use `Composer\Script\Event` as the event parameter type for supported script callbacks.

Do not put a second signature on `run()` in the standard base pattern. The event is available through `composerEvent()` and `run()` remains `protected function run(): void`.

## Next step

Use [Composer Output](06-composer-output.en.md) for messages, branches and status lines.
