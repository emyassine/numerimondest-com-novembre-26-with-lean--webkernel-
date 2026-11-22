# Webkernel Container

`webkernel/container` is the runtime dependency container. It implements PSR-11, supports explicit transient, singleton, and request-scoped bindings, and autowires class constructor and callable parameters.

```php
use Webkernel\Container\Container;

$container = Container::boot($compiled_bindings);
$container->singleton(Clock::class, SystemClock::class);
$service = $container->get(ReportService::class);
```

Package providers declare class bindings through `ServiceProvider::BINDINGS`. Composer compiles those declarations into `webkernel_bindings.php`; provider discovery does not run during a request. The HTTP entry point boots this map once and clears scoped instances at each request boundary.

Composables are separate extensions. The container does not enumerate or boot them, and the route package does not depend on the composables package.
