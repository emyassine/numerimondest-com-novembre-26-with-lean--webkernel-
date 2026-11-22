# Performance

Path helpers keep repeated access inexpensive by resolving stable information once. The first call and a cached call answer different performance questions.

## Work performed

| Helper | Initialization | Repeated call |
| --- | --- | --- |
| `base_path()` | Root metadata lookup and canonicalization during autoload. | Global read and optional concatenation. |
| `vendor_path()` | Registered-loader lookup on first call. | Static-cache read and optional concatenation. |
| `webkernel_package()` | Metadata lookup, `realpath()` and directory check per package on first use. | Per-package cache lookup and optional concatenation. |

Calling `base_path()` for the first time after Composer autoload does not measure root injection. A warm benchmark of `webkernel_package()` does not measure its first package lookup.

## Local sample

These PHP CLI measurements were reported from a local Webkernel project:

| Helper | First call | Second call | Reported average |
| --- | ---: | ---: | ---: |
| `base_path()` | 2.3 µs | 0.469 µs | 0.061 µs |
| `vendor_path()` | 7.3 µs | 0.951 µs | 0.126 µs |
| `webkernel_package('codebase')` | 65.5 µs | 1.396 µs | 0.154 µs |

The original run's PHP version, OPcache settings, iteration count and timing overhead were not recorded with these results. Treat them as an illustrative local sample, not a reproducible comparison or a universal guarantee.

## Measure your environment

Save this example in the consuming project's root and adjust the autoloader path when needed:

```php
<?php declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

$iterations = 1_000_000;
$benchmarks = [
    'base_path' => static fn (): string => base_path(),
    'vendor_path' => static fn (): string => vendor_path(),
    'webkernel_package' => static fn (): string => webkernel_package('lifecycle'),
];

foreach ($benchmarks as $name => $callback) {
    $callback();
    $started_at = hrtime(true);

    for ($index = 0; $index < $iterations; ++$index) {
        $callback();
    }

    $elapsed = hrtime(true) - $started_at;
    printf("%s: %.3f µs/call\n", $name, $elapsed / $iterations / 1_000);
}
```

This measures warmed callbacks, including loop and closure-call overhead. For cold calls, use a fresh process and time the first lookup separately. Record the PHP version, OPcache and CLI settings, platform, iteration count and whether Composer autoload is inside the measured interval. OPcache must be enabled for Webkernel performance measurements.

The package also contains `support/benchmarks/__bench_lc_functions.php`. Pass the consuming project's Composer autoloader as its first argument:

```sh
php vendor/webkernel/lifecycle/support/benchmarks/__bench_lc_functions.php vendor/autoload.php
```

Adjust both paths when the package or Composer dependencies use a custom layout.

## What the result establishes

A helper microbenchmark measures path access in that process. It does not measure complete request latency, startup, filesystem reads, database work or application rendering. Measure those separately under the application's actual deployment conditions.
