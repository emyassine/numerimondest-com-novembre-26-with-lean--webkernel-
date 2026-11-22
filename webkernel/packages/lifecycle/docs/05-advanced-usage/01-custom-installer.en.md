# Custom Installer

Lifecycle registers `LCBaseInstaller` with Composer's installation manager. It extends `LibraryInstaller` and supports the package types listed below. A package's `type` selects the destination; package-name and parent-module placeholders complete it.

## Current destinations

In this table, `V` means the value returned by `vendor_path()`. The current implementation uses that same base for its module and FFI destinations.

| Package type | Destination |
| --- | --- |
| `webkernel-business-module` | `V/modules/<vendor>/<name>` |
| `webkernel-platform-module` | `V/modules/<vendor>/<name>` |
| `webkernel-business-module-feature` | `V/modules/<parentVendor>/<parentName>/features/<vendor>-<name>` |
| `webkernel-platform-module-feature` | `V/modules/<parentVendor>/<parentName>/features/<vendor>-<name>` |
| `webkernel-ffi` | `V/ffi/<vendor>/<name>` |
| `webkernel-assets` | `V/<vendor>/<name>` |
| `webkernel-component` | `V/<vendor>/<name>` |
| `webkernel-devtool` | `V/<vendor>/<name>` |
| `webkernel-stdlib` | `V/<vendor>/<name>` |
| `webkernel-element` | `V/<vendor>/<name>` |
| `webkernel-agent` | `V/<vendor>/<name>` |

With `vendor-dir: internal/dependencies`, a business module named `acme/billing` therefore resolves to `internal/dependencies/modules/acme/billing` under the root project. The installer currently does not use `base_path('modules')` for that destination. Resolve installed paths through Composer metadata rather than infer them from an application directory convention.

## Declare a package type

```json
{
  "name": "acme/billing",
  "type": "webkernel-business-module",
  "require": {
    "webkernel/lifecycle": "^0.12.0"
  }
}
```

The root project must allow the plugin. Declaring the plugin dependency makes the installer part of the dependency graph; installation and activation remain Composer's responsibility.

## Attach a feature

```json
{
  "name": "acme/invoices",
  "type": "webkernel-business-module-feature",
  "require": {
    "webkernel/lifecycle": "^0.12.0"
  },
  "extra": {
    "webkernel": {
      "module": "acme/billing"
    }
  }
}
```

Both feature types require `extra.webkernel.module` in `vendor/name` form. That field supplies path placeholders; it does not add a Composer dependency on the parent module or verify that the parent is installed. Declare any required parent dependency explicitly in `require`.

## Supported versus ordinary package types

Only the enum's recognized types are claimed by this installer. Normal Composer libraries retain their ordinary installation behavior. There is no manifest option for registering additional destination templates; the current mapping belongs to `LCInstallerLocations`.

## Why use an installer?

Use this integration when a Webkernel package type needs its defined physical placement. If all you need is to find packages at runtime, Composer's package metadata can often provide that without changing placement. See the official [custom installer guide](https://getcomposer.org/doc/articles/custom-installers.md) and the [runtime metadata guide](02-composer-runtime-metadata.en.md).
