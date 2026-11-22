# Declaration Schema

The bundled schema at [`support/schemas/extra-webkernel.v1.json`](../../support/schemas/extra-webkernel.v1.json) is the source of truth for the structure and values of `extra.webkernel`. On `pre-autoload-dump`, Lifecycle validates the root and installed package declarations against this same file through `Webkernel\\Utilities\\JsonSchemaValidator`, before Composer generates the autoloader. An invalid declaration stops the Composer command at that point. Zed can use the schema for editor validation. It is not a replacement for the whole `composer.json` schema.

## Top-level Webkernel keys

| Key under `extra.webkernel` | Value | Current meaning |
| --- | --- | --- |
| `$schema` | Non-empty string. | Schema metadata hint. Lifecycle does not fetch it or configure the editor from it. |
| `provider` | Non-empty string. | Provider metadata; this package validates the value but does not boot a provider from it. |
| `module` | `vendor/name` string. | Required parent reference for feature package destinations. |
| `package-declaration` | PHP class name. | Autoloadable class extending `Webkernel\Packages\PackagesMetadatas`; it provides the package namespace prefix and native PHP catalog metadata at Composer dump time. |
| `lifecycle` | Object. | Checks, events and actions. |
| `x-monorepo` | Object with optional `package-repos` string list. | Validated metadata; Lifecycle does not create repositories or synchronize packages from it. |

Other keys are rejected. Metadata belongs in the class named by `package-declaration`, not in duplicated JSON arrays. Its `METADATA` constant may provide `prefix` and `catalog`; catalog text uses locale-keyed arrays such as `['fr' => 'Routage', 'en' => 'Routing']`. Composer evaluates the declaration while compiling manifests, and requests consume the generated PHP array. A schema hint nested under `extra.webkernel` does not automatically make an editor apply a JSON schema to that object; configure editor associations or schema composition when needed. Use a path appropriate to the declaring manifest if you include a local hint.

```json
{"extra":{"webkernel":{"package-declaration":"Acme\\Routing\\Providers\\RoutingPDeclaration"}}}
```

```php
namespace Acme\Routing\Providers;

use Webkernel\Packages\PackagesMetadatas;

final class RoutingPDeclaration extends PackagesMetadatas
{
    protected const array METADATA = [
        'prefix' => 'routing',
        'catalog' => [
            'title' => ['fr' => 'Routage', 'en' => 'Routing'],
            'summary' => ['fr' => 'Routage HTTP.', 'en' => 'HTTP routing.'],
            'category' => ['fr' => 'Fondations', 'en' => 'Foundation'],
            'features' => [],
            'visibility' => 'public',
            'type' => 'http',
        ],
    ];
}
```

The `module` grammar is `^[a-z0-9_-]+/[a-z0-9_-]+$`: lowercase letters, digits, underscores and hyphens on each side of one slash. This is the accepted parent-reference grammar, even if another Composer package name could otherwise contain additional characters.

## Lifecycle keys

| Key | Shape | Empty value |
| --- | --- | --- |
| `checks` | List of check objects. | `[]` means no checks from this package. |
| `events` | Supported event names mapped to callback strings or non-empty callback lists. | `{}` means no event callbacks. |
| `actions` | List of class-name strings. | `[]` means no actions from this package. |

All sections are optional. Callback and action class references use PHP identifiers separated by backslashes. JSON must escape each backslash. The runtime declaration grammar does not accept a leading backslash. Only event callbacks accept a `::method` suffix; action entries name classes only.

## Check object

| Field | Required | Allowed value |
| --- | --- | --- |
| `check` | Yes. | `PHP_VERSION_ID` comparison to an integer. |
| `on-fail` | Yes. | Object containing `type` and `message`. |
| `on-fail.type` | Yes. | `info`, `warning` or `danger`. |
| `on-fail.message` | Yes. | Non-empty string. |
| `fix` | No. | Non-empty informational string. |

## Complete example

```json
{
  "extra": {
    "webkernel": {
      "lifecycle": {
        "checks": [
          {
            "check": "PHP_VERSION_ID < 80500",
            "on-fail": { "type": "danger", "message": "PHP 8.5 or newer is required." }
          }
        ],
        "events": {
          "post-install-cmd": "Acme\\Build\\AfterInstall"
        },
        "actions": ["Acme\\Build\\GenerateManifest"]
      }
    }
  }
}
```

Lifecycle also checks the package-type rule that feature packages require `module`; that rule depends on Composer's package type and is not part of the standalone `extra.webkernel` schema. An editor accepting a document does not establish that a callback class is invokable or implements the action contract.

## Related references

See [Supported Events](02-supported-events.en.md), [Supported Checks](03-supported-checks.en.md), [Public API](04-public-api.en.md) and the [Glossary](05-glossary.en.md).
