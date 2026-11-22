# Webkernel Routing

Routes dispatch HTTP methods and paths to handlers. In an HTTP request, handlers
can type-hint `Webkernel\Route\Request` to read query parameters, form input,
uploaded files, cookies, and request headers:

```php
use Webkernel\Route\Request;

Route::post('/profile', static function (Request $request): string {
    $display_name = $request->input('display_name', '');

    return is_string($display_name) ? $display_name : '';
});
```

`input()` prefers submitted body fields over query parameters. `query()` and
`body()` return those sources separately; `all()` merges them with body values
taking precedence. Input remains untrusted and must be validated and authorized
before it is used for a state change.

`request()` and `original_request()` return the same current Webkernel request.
The request provides query lookup, host, path, client IP, user agent, and
`routeIs()` access. `route()` returns a read-only `CurrentRoute` with its name
and resolved parameters; `request('field')` reads merged input.

Route extensions can register macros with `Route::macro('livewire', $callback)` and global parameter constraints with `Route::pattern('tenant', '[a-z0-9.-]+')`. Packages that need macros available inside application route files declare a static callback through `ServiceProvider::ROUTE_BOOTSTRAPPERS`; Webkernel runs it before loading any declared route file. Middleware names may be registered as aliases or ordered groups using `Route::middlewareAlias()` and `Route::middlewareGroup()`. `Route::appendMiddlewareGroup()` adds package middleware without replacing the application's group. During dispatch, `Route::current()` exposes the matched route, parameters, and declared attributes.
