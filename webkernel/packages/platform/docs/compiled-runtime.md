# Compiled panel templates

`Panel::make()->...` remains the panel declaration entry point. A panel may declare a template built from typed nodes:

```php
Panel::make()
    ->id('control')
    ->compiledTemplate('dashboard', [
        new Literal('<main><h1>Dashboard</h1><p>'),
        new Hole(['user', 'name']),
        new Literal('</p></main>'),
    ], [
        'reports-viewer-v1' => [
            new Literal('<main><h1>Reports</h1></main>'),
        ],
    ]);
```

`./platform deploy` compiles these declarations into immutable PHP closures under the `compiled-panels` deployment artifact. Literal HTML is emitted as a static PHP string. `Hole` values are escaped, user fields are limited to the immutable identity projection, and `AbilityBlock` emits a bitmask check. Role signatures select separately compiled files; if a panel declared role variants and the request frame does not match one, the renderer returns `null` so the caller can use its normal authorization-aware path. Unsupported nodes fail the build.

The application supplies a `Frame` only after its normal identity and permission checks. `CompiledPanelTemplates::render()` reads the active deployment artifact and executes one compiled template without constructing the component tree. The current Platform route middleware and provider lifecycle remain in place; applications opt in by invoking this renderer from an authorized page handler. This is an explicit compiled-template path, not an automatic replacement for every existing page or widget.

## Benchmarking

Start the target server with `WEBKERNEL_PROFILE_REQUEST=1` so its `Server-Timing` header is available, then run:

```sh
WEBKERNEL_BENCHMARK_COOKIE='session-cookie=value' php support/benchmarks/compiled-panel.php http://127.0.0.1:8080/control 200
```

The script records the first successful full response separately, then sends 200 warm requests and reports `hrtime(true)` p50/p95 plus p50 values from `Server-Timing`. Record the server's PHP version, SAPI, OPcache/JIT settings, process state, data fixture and whether the URL returned the authenticated dashboard before comparing results. Do not use a login page, redirect, or placeholder as the dashboard benchmark.

The PHP worker transport is optional. Callers should enable it only after the host probe succeeds and representative p50 measurements show a clear improvement over the in-process path. Its socket is private, each request carries an HMAC token, and a failed socket connection falls back to the current in-process renderer.

Run `./platform panel:runtime-probe begin`, let that command's request/process finish, then run `./platform panel:runtime-probe verify`. The probe creates files only under `storage/private/panel-runtime`; a positive result establishes child survival and Unix socket availability for that host, but does not establish a latency win or permission under the hosting provider's terms.
