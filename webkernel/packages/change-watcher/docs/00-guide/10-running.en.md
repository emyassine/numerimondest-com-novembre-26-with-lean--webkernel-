# Running the Change Watcher

The `change-watcher` Composer binary runs in the foreground. Use a process supervisor to keep it running, or use `--once` from a build script. It starts no subprocesses and requires no operating-system file notification extension.

## Definitions file

Create an application-owned PHP file that returns a callable accepting a watcher:

```php
<?php declare(strict_types=1);

namespace App\Cache;

use RuntimeException;
use Webkernel\ChangeWatcher\Watcher;

return static function (Watcher $watcher): void {
    $watcher->watch(
        id: 'documents:help',
        sources: [dirname(__DIR__, 2) . '/app/resources/help.html'],
        compiler: static function (array $sources): string {
            $contents = file_get_contents($sources[0]);
            if ($contents === false) {
                throw new RuntimeException('Unable to read the help document.');
            }

            return $contents;
        },
    );

    $watcher->watchResource('remote:vendor-schema', static function (): string {
        $handle = curl_init('https://example.com/schema.json');
        curl_setopt_array($handle, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 3]);
        $contents = curl_exec($handle);
        $status = curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $error = curl_error($handle);
        curl_close($handle);
        if ($contents === false || $status < 200 || $status >= 300) {
            throw new RuntimeException($error !== '' ? $error : 'Schema request failed with HTTP ' . $status);
        }

        return $contents;
    });
};
```

The remote example's transport function belongs to the application. The watcher does not choose a network client.

## Continuous polling

```bash
/srv/application/internal/dependencies/bin/change-watcher \
    --definitions=/srv/application/internal/cache-watches.php \
    --cache=/srv/application/runtime/cache/change-watcher \
    --interval=250
```

Replace `/srv/application` with the absolute application root. Composer's binary proxy provides the autoloader. When invoking the package executable directly, also pass `--autoload=/absolute/path/to/vendor/autoload.php`.

The interval is the pause after a pass, in milliseconds. It defaults to 250; valid values range from 1 to 60000. Actual detection delay also includes time to read every registered source and run required compilers. Smaller intervals increase file reads and remote requests.

The definitions file is content-checked before each pass and reloaded when it changes. Its content hash becomes the registry revision and therefore invalidates registered artifact targets. Definitions return closures; changing already-loaded PHP classes requires a process restart.

## One pass

Append `--once` to inspect and compile one pass, then exit. It returns `0` when all artifact targets are ready and `1` on setup errors, compiler errors or inputs changing while compilation runs. A watcher containing only read-only checks returns successfully after observations. Publications go to stdout and errors go to stderr.

Run one publisher per cache store. The store uses an exclusive lock so overlapping pollers fail rather than compile and publish concurrently.
