# Webkernel Filesystem

This package contains the Laravel Framework 13.38.0 filesystem component
adapted to the `Webkernel\\Filesystem` namespace. Source attribution and the
MIT license are preserved in `LICENSE-LARAVEL.md`.

Laravel's service provider and global helper file are excluded. Webkernel owns
package registration and service construction.

The internal storage engine is under `Webkernel\Filesystem\Storage`. Its core,
local adapter and MIME detection code originate from the reference vendor tree;
their license notices are retained next to the source. The package has no Laravel framework dependency
imports in this package. External runtime dependencies are PSR and Symfony.

`FilesystemManager` accepts a configuration array (`default`, `cloud`, `disks`)
and an optional PSR logger. Local and read-through disks are built in. Additional
drivers are registered explicitly with `extend()`. Unavailable prefixed/read-only
drivers fail explicitly rather than silently dropping their restrictions.

`ServeFile` and `ReceiveFile` receive a disk and an authorization callback from
the routing integration. They do not register routes or resolve Laravel facades.
Temporary URLs require callbacks supplied by the routing integration.
PHPUnit assertion helpers and the unrelated Laravel image factory are excluded.

AWS-specific URL support lives in the optional `webkernel/filesystem-aws` package.
The S3 storage driver, FTP/SFTP adapters, and application route/provider wiring
are not implemented here. The filesystem is not yet wired to Filament's remaining
Storage facade calls. No full-panel latency result is established by this port.
