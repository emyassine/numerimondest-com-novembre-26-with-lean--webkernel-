# Supported Checks

Check expressions describe failure conditions. They are parsed as data and evaluate against the PHP process running Composer.

## Grammar

```text
PHP_VERSION_ID <operator> <integer>
```

Whitespace is allowed around the expression and operator. The integer is non-negative decimal digits. Variable names are case-sensitive; only `PHP_VERSION_ID` is supported.

| Operator | Failure when |
| --- | --- |
| `==`, `===` | The version ID equals the integer. |
| `!=`, `!==` | The version ID differs from the integer. |
| `<` | The version ID is lower. |
| `<=` | The version ID is lower or equal. |
| `>` | The version ID is higher. |
| `>=` | The version ID is higher or equal. |

The equality variants behave the same here because both operands are integers. PHP 8.5.0 has version ID `80500`.

## Results

| Comparison result | Configured severity | Behavior |
| --- | --- | --- |
| False | Any supported severity. | Reports `PASS`. |
| True | `info` | Reports `INFO`, continues. |
| True | `warning` | Reports `WARN`, continues. |
| True | `danger` | Reports `BLOCKED`, throws. |

`on-fail.message` is required. `fix` is optional text included with a failure and is never executed. Unknown fields, unknown severities, unsupported expressions and missing required values fail declaration validation.

## Unsupported forms

Extension checks, `&&`, `||`, parentheses, arbitrary constants, functions, environment variables and general PHP expressions are not part of the current language. Express mandatory extension requirements through Composer's `require` metadata.

## Related guide

See [Environment Checks](../03-guides/04-environment-checks.en.md) for a full manifest example and built-in execution phases.
