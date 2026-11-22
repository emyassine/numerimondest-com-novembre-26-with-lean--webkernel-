# Declaring Metadata

Put a declaration in the `composer.json` of the package that owns the task. Lifecycle reads the root package and installed dependencies when it handles a supported event.

## 1. Add a lifecycle section

```json
{
  "extra": {
    "webkernel": {
      "lifecycle": {
        "checks": [],
        "events": {},
        "actions": []
      }
    }
  }
}
```

Each section is optional. Merge this object into the existing manifest; keep the package's other Composer fields.

| Key | Use it for |
| --- | --- |
| `checks` | Supported PHP-version failure conditions. |
| `events` | Callbacks attached to a supported Composer script event. |
| `actions` | Action classes run by the built-in post-autoload worker. |

## 2. Make classes autoloadable

```json
{
  "autoload": {
    "psr-4": { "Acme\\Build\\": "src/" }
  },
  "extra": {
    "webkernel": {
      "lifecycle": {
        "actions": ["Acme\\Build\\GenerateManifest"]
      }
    }
  }
}
```

Place `Acme\Build\GenerateManifest` at `src/GenerateManifest.php`. Use normal `autoload` metadata for callbacks that must be available in consuming projects; a dependency's development autoload rules are not a production extension contract.

## 3. Choose class references

| Reference | Resolution |
| --- | --- |
| `Acme\Build\GenerateManifest` | Used directly if the class is autoloadable. |
| `GenerateManifest` | Resolved under the declaring package's sole PSR-4 prefix. |
| `Events\AfterDump::run` | Prefixes the class while retaining the method suffix. |

Use fully qualified, autoloadable names when a package has multiple PSR-4 prefixes. Write them without a leading backslash in JSON; the declaration validator's class-name grammar does not accept that prefix. Missing fully qualified classes still fail resolution or target validation.

## How declarations are read

The root declaration comes from Composer's root package object. For installed packages, Lifecycle reads `composer.json` from the installation manager's resolved path; if that manifest is unavailable, it falls back to Composer's stored package metadata. Read or JSON errors stop the operation.

Validated declarations are cached for the Composer instance. Package install/update/uninstall events invalidate that cache. Do not depend on editing manifests halfway through a running event to change an already collected job list.

## Next step

Choose [Composer Events](02-composer-events.en.md), [Checks](04-environment-checks.en.md) or [Actions](05-writing-actions.en.md). The complete key list is in the [Declaration Schema](../06-reference/01-declaration-schema.en.md).
