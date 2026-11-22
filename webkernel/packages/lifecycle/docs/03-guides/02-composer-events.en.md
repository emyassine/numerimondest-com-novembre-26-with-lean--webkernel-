# Composer Events

Use an event declaration when work belongs to a specific Composer phase. Lifecycle accepts the eight script event names listed in the [event reference](../06-reference/02-supported-events.en.md).

## 1. Choose the phase

`post-autoload-dump` is the usual choice when work needs the newly generated autoloader. Install/update pre-events run earlier and must account for unavailable dependencies. If you only need to generate an artifact after autoload, consider a declared [action](05-writing-actions.en.md).

Lifecycle itself validates every root and installed `extra.webkernel` declaration on `pre-autoload-dump`. This happens before Composer generates the autoloader, so malformed declarations fail at the start of that generation step. The validation does not depend on runtime helpers such as `webkernel_package()`.

## 2. Declare a callback

```json
{
  "extra": {
    "webkernel": {
      "lifecycle": {
        "events": {
          "post-autoload-dump": "Acme\\Build\\AfterAutoloadDump"
        }
      }
    }
  }
}
```

The class must be invokable. A target such as `Acme\Build\Hooks::afterDump` can instead name a public static or instance method. For multiple callbacks, use a non-empty array:

```json
{
  "post-autoload-dump": [
    "Acme\\Build\\AfterAutoloadDump",
    "Acme\\Build\\Hooks::afterDump"
  ]
}
```

This second snippet is the value of `extra.webkernel.lifecycle.events`.

## Execution order

Callbacks within a package retain declaration order. Across packages, `webkernel/codebase` has priority; remaining package names are sorted alphabetically, including a named root package. Root declaration discovery happens first, but that does not give root callbacks first execution priority.

There is no declaration field for custom priorities or callback dependencies. Keep related steps in one ordered callback list or one focused task rather than relying on unrelated package names.

## Validation and failure

Lifecycle resolves and validates every target for the current event before invoking its first callback. A callback must have a public target method and at most one required event argument. Instance callbacks must be instantiable without required constructor arguments. When the method declares parameters, Lifecycle passes one event object; other optional parameters retain their defaults.

An exception stops the event and propagates to Composer. Earlier callback effects are not rolled back. For action-specific continue-on-error behavior, use the action runner described in [Writing Actions](05-writing-actions.en.md).

## Next step

Write the callback class in [Writing Listeners](03-writing-listeners.en.md).
