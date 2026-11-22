# Webkernel Utilities: JSON Schema
`webkernel/utilities-json-schema` validates JSON-compatible PHP values against JSON Schema documents. It supports [Draft 3, 4, 6, 7, and 2019-09](#what-json-schema-drafts-mean).

The package has no Composer runtime dependencies. It requires PHP 8.5 or newer, `ext-filter`, and `ext-json`. Its public API and native backed enums use the `Webkernel\Utilities\JsonSchema` namespace.

## Installation

```sh
composer require webkernel/utilities-json-schema
```

## Validate a decoded JSON document

```php
<?php declare(strict_types=1);

use Webkernel\Utilities\JsonSchema\Validator;

$data = json_decode(file_get_contents('data.json'), false, flags: JSON_THROW_ON_ERROR);
$schema = json_decode(file_get_contents('schema.json'), false, flags: JSON_THROW_ON_ERROR);

$validator = new Validator();
$validator->validate($data, $schema);

if (! $validator->isValid()) {
    foreach ($validator->getErrors() as $error) {
        printf("[%s] %s\n", $error['property'], $error['message']);
    }
}
```

## Validate against a schema file

`Webkernel\Utilities\JsonSchemaValidator` loads and caches the schema file, then returns its violations:

```php
<?php declare(strict_types=1);

use Webkernel\Utilities\JsonSchemaValidator;

$errors = (new JsonSchemaValidator())->validate($data, __DIR__ . '/schema.json');
```

For the full validator API and configuration options, see the sections below and [Understanding JSON Schema](https://json-schema.org/understanding-json-schema/).

### Type coercion

If you're validating data passed to your application via HTTP, you can cast strings and booleans to
the expected types defined by your schema:

```php
<?php declare(strict_types=1);

use Webkernel\Utilities\JsonSchema\SchemaStorage;
use Webkernel\Utilities\JsonSchema\Validator;
use Webkernel\Utilities\JsonSchema\Constraints\Factory;
use Webkernel\Utilities\JsonSchema\Constraints\Constraint;

$request = (object)[
    'processRefund'=>"true",
    'refundAmount'=>"17"
];

$validator->validate(
    $request, (object) [
        "type"=>"object",
        "properties"=>(object)[
            "processRefund"=>(object)[
                "type"=>"boolean"
            ],
            "refundAmount"=>(object)[
                "type"=>"number"
            ]
        ]
    ],
    Constraint::CHECK_MODE_COERCE_TYPES
); // validates!

is_bool($request->processRefund); // true
is_int($request->refundAmount); // true
```

A shorthand method is also available:
```php
<?php declare(strict_types=1);
use Webkernel\Utilities\JsonSchema\Validator;
use Webkernel\Utilities\JsonSchema\Constraints\Constraint;

$validator = new Validator();
$validator->coerce($request, $schema);
// equivalent to $validator->validate($data, $schema, Constraint::CHECK_MODE_COERCE_TYPES);
```

### Default values

If your schema contains default values, you can have these automatically applied during validation:

```php
<?php declare(strict_types=1);

use Webkernel\Utilities\JsonSchema\Validator;
use Webkernel\Utilities\JsonSchema\Constraints\Constraint;

$request = (object)[
    'refundAmount'=>17
];

$validator = new Validator();

$validator->validate(
    $request,
    (object)[
        "type"=>"object",
        "properties"=>(object)[
            "processRefund"=>(object)[
                "type"=>"boolean",
                "default"=>true
            ]
        ]
    ],
    Constraint::CHECK_MODE_APPLY_DEFAULTS
); //validates, and sets defaults for missing properties

is_bool($request->processRefund); // true
$request->processRefund; // true
```

### With inline references

```php
<?php declare(strict_types=1);

use Webkernel\Utilities\JsonSchema\SchemaStorage;
use Webkernel\Utilities\JsonSchema\Validator;
use Webkernel\Utilities\JsonSchema\Constraints\Factory;

$jsonSchema = <<<'JSON'
{
    "type": "object",
    "properties": {
        "data": {
            "oneOf": [
                { "$ref": "#/definitions/integerData" },
                { "$ref": "#/definitions/stringData" }
            ]
        }
    },
    "required": ["data"],
    "definitions": {
        "integerData" : {
            "type": "integer",
            "minimum" : 0
        },
        "stringData" : {
            "type": "string"
        }
    }
}
JSON;

// Schema must be decoded before it can be used for validation
$jsonSchemaObject = json_decode($jsonSchema);

// The SchemaStorage can resolve references, loading additional schemas from file as needed, etc.
$schemaStorage = new SchemaStorage();

// This does two things:
// 1) Mutates $jsonSchemaObject to normalize the references (to file://mySchema#/definitions/integerData, etc)
// 2) Tells $schemaStorage that references to file://mySchema... should be resolved by looking in $jsonSchemaObject
$schemaStorage->addSchema('file://mySchema', $jsonSchemaObject);

// Provide $schemaStorage to the Validator so that references can be resolved during validation
$jsonValidator = new Validator(new Factory($schemaStorage));

// JSON must be decoded before it can be validated
$jsonToValidateObject = json_decode('{"data":123}');

// Do validation (use isValid() and getErrors() to check the result)
$jsonValidator->validate($jsonToValidateObject, $jsonSchemaObject);
```

### Configuration Options
A number of flags are available to alter the behavior of the validator. These can be passed as the
third argument to `Validator::validate()`, or can be provided as the third argument to
`Factory::__construct()` if you wish to persist them across multiple `validate()` calls.

| Flag                                            | Description                                                     |
|-------------------------------------------------|-----------------------------------------------------------------|
| `Constraint::CHECK_MODE_NORMAL`                 | Validate in 'normal' mode - this is the default                 |
| `Constraint::CHECK_MODE_TYPE_CAST`              | Enable fuzzy type checking for associative arrays and objects   |
| `Constraint::CHECK_MODE_COERCE_TYPES` [^1][^2]  | Convert data types to match the schema where possible           |
| `Constraint::CHECK_MODE_EARLY_COERCE` [^2]      | Apply type coercion as soon as possible                         |
| `Constraint::CHECK_MODE_APPLY_DEFAULTS` [^1]    | Apply default values from the schema if not set                 |
| `Constraint::CHECK_MODE_ONLY_REQUIRED_DEFAULTS` | When applying defaults, only set values that are required       |
| `Constraint::CHECK_MODE_EXCEPTIONS`             | Throw an exception immediately if validation fails              |
| `Constraint::CHECK_MODE_DISABLE_FORMAT`         | Do not validate "format" constraints                            |
| `Constraint::CHECK_MODE_VALIDATE_SCHEMA`        | Validate the schema as well as the provided document            |
| `Constraint::CHECK_MODE_STRICT` [^3]            | Validate the scheme using strict mode using the specified draft |

[^1]: Please note that using `CHECK_MODE_COERCE_TYPES` or `CHECK_MODE_APPLY_DEFAULTS` will modify your
original data.
[^2]: `CHECK_MODE_EARLY_COERCE` has no effect unless used in combination with `CHECK_MODE_COERCE_TYPES`. If
enabled, the validator will use (and coerce) the first compatible type it encounters, even if the
schema defines another type that matches directly and does not require coercion.
[^3]: `CHECK_MODE_STRICT` only can be used for Draft-6, Draft 7 or Draft 2019-09 at this point.


## What JSON Schema Drafts mean

In JSON Schema, a **Draft** is a published version of the specification. The word is part of the specification's versioning terminology; in practice, a Draft identifies the rules and vocabulary that a schema document follows. It does not mean that an application should treat its schema as an unfinished document.

The specification has evolved over time. Later Drafts add keywords and refine how existing keywords behave. As a result, the same schema document can be interpreted differently when validated against different Drafts. The `$schema` property identifies the version the document uses by giving its official schema URI.

For example, this declaration selects Draft 7:

```json
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "type": "object",
  "properties": {
    "email": { "type": "string", "format": "email" }
  }
}
```

This package supports Draft 3, Draft 4, Draft 6, Draft 7, and Draft 2019-09. In normal validation, the validator uses its default behavior. Enabling `Constraint::CHECK_MODE_STRICT` makes it select the constraint set identified by `$schema`; when `$schema` is absent, it uses Draft 6 as the default dialect. This matters when a schema relies on keywords whose meaning or availability differs between versions.
