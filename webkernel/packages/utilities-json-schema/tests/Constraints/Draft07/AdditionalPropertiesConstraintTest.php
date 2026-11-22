<?php

declare(strict_types=1);

namespace Webkernel\Utilities\JsonSchema\Tests\Constraints\Draft07;

use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft07\AdditionalPropertiesConstraint;
use Webkernel\Utilities\JsonSchema\Tests\Constraints\VeryBaseTestCase;

class AdditionalPropertiesConstraintTest extends VeryBaseTestCase
{
    public function testSubSchemaErrorsAreReportedAtThePropertyPath(): void
    {
        $constraint = new AdditionalPropertiesConstraint();
        $schema = json_decode('{"properties": {"foo": {}}, "additionalProperties": {"type": "integer"}}');
        $value = json_decode('{"foo": "ok", "bar": "not-an-integer"}');

        $constraint->check($value, $schema);

        $errors = $constraint->getErrors();
        $this->assertCount(1, $errors);
        $this->assertSame('/bar', $errors[0]['pointer']);
        $this->assertSame('type', $errors[0]['constraint']['name']);
    }

    public function testAPropertyMatchingTheSubSchemaIsNotReported(): void
    {
        $constraint = new AdditionalPropertiesConstraint();
        $schema = json_decode('{"additionalProperties": {"type": "integer"}}');
        $value = json_decode('{"bar": 1}');

        $constraint->check($value, $schema);

        $this->assertSame([], $constraint->getErrors());
    }

    public function testAdditionalPropertiesFalseStillReportsTheProperty(): void
    {
        $constraint = new AdditionalPropertiesConstraint();
        $schema = json_decode('{"properties": {"foo": {}}, "additionalProperties": false}');
        $value = json_decode('{"foo": "ok", "bar": 1}');

        $constraint->check($value, $schema);

        $errors = $constraint->getErrors();
        $this->assertCount(1, $errors);
        $this->assertSame('/bar', $errors[0]['pointer']);
        $this->assertSame('additionalProp', $errors[0]['constraint']['name']);
    }
}
