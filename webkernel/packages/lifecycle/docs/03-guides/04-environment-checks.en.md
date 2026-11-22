# Environment Checks

A check describes a failed environment requirement and how Lifecycle should react. Built-in workers run declared checks before install/update and after autoload generation, whenever the plugin is active for those events.

## 1. Declare a failure condition

```json
{
  "extra": {
    "webkernel": {
      "lifecycle": {
        "checks": [
          {
            "check": "PHP_VERSION_ID < 80500",
            "on-fail": {
              "type": "danger",
              "message": "PHP 8.5 or newer is required."
            },
            "fix": "Run Composer with PHP 8.5 or newer."
          }
        ]
      }
    }
  }
}
```

The comparison means **failure when true**. Here, PHP below 8.5 triggers `danger`; PHP 8.5 or newer passes. It checks the PHP process running Composer, not another PHP binary used by a web server.

## 2. Choose the severity

| Type | Result | Composer operation |
| --- | --- | --- |
| `info` | Informational failure message. | Continues. |
| `warning` | Warning message. | Continues. |
| `danger` | Blocking failure message and exception. | Stops immediately. |

`fix` is optional text shown with a failure. It is never executed, even if it looks like a command. A passing comparison reports `PASS`. The runner also reports totals and elapsed time.

## Supported expressions

The current language accepts `PHP_VERSION_ID`, one comparison operator and a non-negative integer. It supports `===`, `==`, `!==`, `!=`, `<`, `<=`, `>`, and `>=`. It does not accept function calls, extension checks, boolean combinations or arbitrary PHP. Expressions are parsed as data.

Keep mandatory platform requirements in Composer's `require` section. Lifecycle checks add phase-specific messages; they cannot protect an event that occurs before the local plugin is installed. See [Composer-time and Runtime](../02-core-concepts/03-composer-time-and-runtime.en.md).

## Ordering

The check runner follows declaration-reader order: root first, then the installed repository's package order. Within each package, checks keep list order. A blocking check prevents later checks and the following action phase from running.

## Next step

Read [Writing Actions](05-writing-actions.en.md), or consult the exact [check reference](../06-reference/03-supported-checks.en.md).
