<?php

declare(strict_types=1);

namespace Webkernel\Utilities\JsonSchema\Tests\Constraints\Drafts\Draft2019;

use Webkernel\Utilities\JsonSchema\Constraints\Constraint;
use Webkernel\Utilities\JsonSchema\DraftIdentifiers;
use Webkernel\Utilities\JsonSchema\Tests\Constraints\BaseTestCase;

class UnevaluatedPropertiesTest extends BaseTestCase
{
    protected $schemaSpec = DraftIdentifiers::Draft201909->value;

    public function getInvalidTests(): \Generator
    {
        yield [
            '{"hello":"world","world":"hello","unexpected":true}',
            '{
                "$schema":"' . DraftIdentifiers::Draft201909->value . '",
                "type":"object",
                "unevaluatedProperties":false,
                "allOf":[
                    {"properties":{"hello":{"type":"string"}},"required":["hello"]},
                    {"properties":{"world":{"type":"string"}},"required":["world"]}
                ]
            }',
            Constraint::CHECK_MODE_STRICT,
        ];

        yield [
            '{"foo":"foo","bar":"bar","baz":"not-baz"}',
            '{
                "$schema":"' . DraftIdentifiers::Draft201909->value . '",
                "type":"object",
                "unevaluatedProperties":false,
                "anyOf":[
                    {"properties":{"foo":{"const":"foo"}}},
                    {"properties":{"bar":{"const":"bar"}}}
                ]
            }',
            Constraint::CHECK_MODE_STRICT,
        ];

        yield [
            '{"kind":"a","value":"ok","extra":true}',
            '{
                "$schema":"' . DraftIdentifiers::Draft201909->value . '",
                "type":"object",
                "unevaluatedProperties":false,
                "if":{"properties":{"kind":{"const":"a"}}},
                "then":{"properties":{"value":{"type":"string"}}}
            }',
            Constraint::CHECK_MODE_STRICT,
        ];
    }

    public function getValidTests(): \Generator
    {
        yield [
            '{"foo":"foo","bar":"bar","qux":"qux","trigger":"on"}',
            '{
                "$schema":"' . DraftIdentifiers::Draft201909->value . '",
                "type":"object",
                "properties":{"trigger":{"type":"string"}},
                "unevaluatedProperties":false,
                "allOf":[true,{"properties":{"foo":{"type":"string"}}}],
                "anyOf":[true,{"properties":{"bar":{"type":"string"}}}],
                "oneOf":[true],
                "if":true,
                "then":{"properties":{"qux":{"type":"string"}}},
                "dependentSchemas":{"trigger":true}
            }',
            Constraint::CHECK_MODE_STRICT,
        ];

        yield [
            '{"hello":"world","world":"hello"}',
            '{
                "$schema":"' . DraftIdentifiers::Draft201909->value . '",
                "type":"object",
                "unevaluatedProperties":false,
                "allOf":[
                    {"properties":{"hello":{"type":"string"}},"required":["hello"]},
                    {"properties":{"world":{"type":"string"}},"required":["world"]}
                ]
            }',
            Constraint::CHECK_MODE_STRICT,
        ];

        yield [
            '{"foo":"ok"}',
            '{
                "$schema":"' . DraftIdentifiers::Draft201909->value . '",
                "type":"object",
                "unevaluatedProperties":false,
                "additionalProperties":{"type":"string"}
            }',
            Constraint::CHECK_MODE_STRICT,
        ];

        yield [
            '{"foo":"foo","bar":"bar"}',
            '{
                "$schema":"' . DraftIdentifiers::Draft201909->value . '",
                "type":"object",
                "unevaluatedProperties":false,
                "anyOf":[
                    {"properties":{"foo":{"const":"foo"}}},
                    {"properties":{"bar":{"const":"bar"}}}
                ]
            }',
            Constraint::CHECK_MODE_STRICT,
        ];

        yield [
            '{"kind":"a","value":"ok"}',
            '{
                "$schema":"' . DraftIdentifiers::Draft201909->value . '",
                "type":"object",
                "unevaluatedProperties":false,
                "if":{"properties":{"kind":{"const":"a"}}},
                "then":{"properties":{"value":{"type":"string"}}}
            }',
            Constraint::CHECK_MODE_STRICT,
        ];

        yield [
            '{"foo":"foo","bar":"bar"}',
            '{
                "$schema":"' . DraftIdentifiers::Draft201909->value . '",
                "type":"object",
                "properties":{"foo":{"type":"string"}},
                "unevaluatedProperties":false,
                "dependentSchemas":{"foo":{"properties":{"bar":{"type":"string"}}}}
            }',
            Constraint::CHECK_MODE_STRICT,
        ];
    }
}
