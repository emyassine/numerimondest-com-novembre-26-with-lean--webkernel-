# Webkernel Filesystem AWS

Optional S3 URL and signed-upload integration using the official AWS SDK.
This package adapts the S3 wrapper from the reference Laravel filesystem source;
see `LICENSE-LARAVEL.md`. The essential filesystem package does not require it.

`S3Adapter` receives an AWS client, an internal storage operator and an internal
storage adapter. The low-level S3 storage driver and its factory registration
are not yet ported; the reference vendor tree contains no Flysystem S3 driver.
The package is not currently installed or wired into application disk configuration.
