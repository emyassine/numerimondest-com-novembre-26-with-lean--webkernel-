# Webkernel Utilities

Generic PHP primitives internalized from the local Laravel reference source:
conditional callbacks, macros, method forwarding, arrays, eager/lazy collections,
and their serialization contracts. See `LICENSE-LARAVEL.md` for attribution.

Composer autoloads the collection helpers and enum-value function. Symfony's
PHP 8.6 polyfill supplies `SortDirection` on the project's PHP 8.5 runtime.
This package does not import Laravel or require its container or facades.

Collections do not include Laravel's automatic HTTP resource discovery. Lazy
collection timeouts use PHP's clock. Other support features (strings, dates,
pagination and HTTP resources) have not been ported by this package yet.
