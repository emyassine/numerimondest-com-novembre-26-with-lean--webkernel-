# Benchmark & Performance Profiling

Measure cold CLI access, a new key in a loaded section, a new section, PHP-FPM APCu access, full boot and repeated lookups separately. The target is **90 µs or less for the first keyed `config()` call**; current cold CLI measurements are higher.

## Measurement boundaries

| Measurement | Included work |
| --- | --- |
| Missing or stale cache | Discovery, source execution, compilation, cache write and lookup |
| Cold CLI, first keyed access | Read and decode one section file, then resolve the requested key; freshness validation is deferred until shutdown |
| New key, loaded section | Traverse that section's in-memory tree once and memoize the key |
| PHP-FPM, warm section | Stat one section file and fetch its decoded tree from APCu, then resolve the requested key |
| PHP-FPM, cold section | Read and decode the section file, then place its decoded tree in APCu for later requests |
| Full `Config::boot()` | Read compiled PHP cache, compare inputs, check filesystem metadata and load all values |
| Subsequent access | In-memory lookup |
| `config_path()` | Configured path access and optional concatenation |

Do not call `Config::boot()` or read configuration before timing a cold keyed access. Doing so excludes section loading. Measure a new key in an already loaded section separately from a key in a new section. In PHP-FPM, measure cold and APCu-warm section access in separate requests. Time `Config::boot()` and shutdown freshness work separately.

## Example

Run this after the application's Composer autoloader has been required, before any configuration access:

```php
hrtime(true);

$start = hrtime(true);
$value = config('paths.app');
$first_end = hrtime(true);

config('paths.app');
$second_end = hrtime(true);

$iterations = 1_000_000;
$loop_start = hrtime(true);
for ($index = 0; $index < $iterations; $index++) {
    config('paths.app');
}
$loop_end = hrtime(true);

printf("First access: %.3f µs\n", ($first_end - $start) / 1_000);
printf("Second access: %.3f µs\n", ($second_end - $first_end) / 1_000);
printf("Repeated mean: %.3f µs\n", ($loop_end - $loop_start) / $iterations / 1_000);
```

The repeated mean includes loop overhead. Print values after timing, and confirm the requested key exists. A missing key exercises the fallback path. A fresh CLI process cannot reuse its process memory; its disk-cache timing differs from later PHP-FPM requests that share APCu. Include process startup and shutdown separately when measuring command wall time.

## OPcache and filesystem conditions

Record PHP version, SAPI, OPcache settings, whether bytecode is warm, source count, filesystem type, and whether the compiled cache was current. Performance measurements should enable OPcache. A fresh CLI process with `opcache.enable_cli=1` still pays initial bytecode compilation; it does not represent a warmed PHP worker.

Use multiple runs and report their spread. An isolated first-call sample is sensitive to scheduling and filesystem state. A fast repeated mean does not establish the first-call budget.
