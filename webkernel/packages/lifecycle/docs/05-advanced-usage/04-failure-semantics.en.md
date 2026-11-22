# Failure Semantics

Lifecycle distinguishes declaration failures from task execution failures. This determines whether any work starts, whether later tasks run and when Composer fails.

## Behavior by phase

| Failure | Later work | Composer result |
| --- | --- | --- |
| Manifest read/JSON error or invalid declaration | Declaration loading stops. | Exception propagates. |
| Invalid callback target for the current event | No callbacks for that event have started. | Exception propagates. |
| Callback execution exception | Later event callbacks stop. | Exception propagates. |
| `info` or `warning` check failure | Later checks continue. | Continues. |
| `danger` check failure | Later checks and the following action phase stop. | Exception propagates. |
| Invalid action class/contract/constructor | No declared actions have started. | Exception propagates. |
| Action execution exception | Later actions are attempted. | Built-in worker throws after the action phase. |
| Installer path resolution failure | The affected package operation fails. | Exception propagates. |

## Validation boundaries

The declaration validator checks metadata syntax and supported fields across the collected packages. The event dispatcher additionally validates the current event's class and method targets. The action runner separately validates its action classes before executing any action. Shape validation does not prove that every target is available in every Composer phase.

## Output versus control flow

`StandardComposerIO::error()` and `result('FAIL', ...)` format messages. They do not throw automatically. A task must throw an exception to report execution failure to the runner. Check severity and worker exception logic supply the control flow described above.

## Side effects

There is no transaction or automatic rollback across callbacks or actions. If the first action writes an artifact and the second fails, the first artifact remains. Keep actions idempotent, check write results and publish generated state through atomic replacement where consistency requires it.

An action key does not make execution transactional, deduplicate declarations or provide retry handling. A later Composer run invokes the declared work again.

## Next step

See [Troubleshooting](../08-troubleshooting.en.md) for symptom-oriented checks, or the [Public API](../06-reference/04-public-api.en.md) for extension signatures.
