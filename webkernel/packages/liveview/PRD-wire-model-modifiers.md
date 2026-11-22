# `wire:model` modifier layering

## Status

Modifier layering is implemented in `js/directives/wire-model.js`. The former proposal text and test paths were stale: the package has no browser-test suite, and its generated bundles are maintained from the locked package build.

## Behavior

Modifiers before `.live` control when client-side component state synchronizes. Modifiers after `.live` control when a server request runs.

| Binding | Client synchronization | Server request |
| --- | --- | --- |
| `wire:model` | Input event | None |
| `wire:model.blur` | Blur | None |
| `wire:model.change` | Change | None |
| `wire:model.enter` | Enter key | None |
| `wire:model.blur.live` | Blur | After the same synchronization |
| `wire:model.live.blur` | Input event | Blur |
| `wire:model.blur.live.debounce.500ms` | Blur | Debounced after synchronization |
| `wire:model.live` | Input event | Debounced for realtime inputs |

`.lazy` is treated as a client-side change modifier before `.live`; it no longer enables a network request on its own. Use `.lazy.live` or `.change.live` to synchronize on change and send the value to the server.

## Source and verification

- Implementation: `js/directives/wire-model.js`.
- The package build creates the classic and CSP browser bundles from the locked dependencies.
- The repository has no dedicated browser tests for modifier timing. A reproducible build verifies source-to-bundle consistency, not event timing; add focused application-level browser coverage before relying on these interactions in a release.
