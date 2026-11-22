# Webkernel ORM

This package contains only the Laravel Framework 13.38.0 Eloquent ORM source
copied from Laravel Eloquent and namespaced under `Webkernel\\ORM`.
The copied source is licensed under the MIT License; see `LICENSE-LARAVEL.md`.

The original upstream revision is `c42f9abd4a3d2f9a3cd6be109255ddce8092ceb8`.

The package depends on Webkernel's database and utilities packages. Eloquent's
builder, partial-relation and cast contracts live in `Webkernel\ORM\Contracts`.
Collections and shared traits use `Webkernel\Utilities`; SQL expression contracts
and implementations belong to `Webkernel\Database`.

This is still an incomplete runtime port. The full SQL query builder/connection
bridge, pagination, date/string support, events, queue contracts, HTTP resources,
and encryption/authentication dependencies remain to be integrated. Remaining
Laravel references must be resolved before installing this as an independent
ORM runtime. Namespace changes alone do not make the ORM operational.
