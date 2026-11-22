<?php

declare(strict_types=1);

namespace Webkernel\Utilities\JsonSchema\Tests\Constraints\Draft06;

use Webkernel\Utilities\JsonSchema\Constraints\Drafts\Draft06\PatternConstraint;
use Webkernel\Utilities\JsonSchema\Tests\Constraints\VeryBaseTestCase;

class PatternConstraintTest extends VeryBaseTestCase
{
    public function testErrorMessageContainsThePattern(): void
    {
        $constraint = new PatternConstraint();
        $schema = json_decode('{"pattern": "^[a-z]+$"}');
        $value = 'ABC';

        $constraint->check($value, $schema);

        $errors = $constraint->getErrors();
        $this->assertCount(1, $errors);
        $this->assertSame('Does not match the regex pattern ^[a-z]+$', $errors[0]['message']);
        $this->assertSame(['pattern' => '^[a-z]+$', 'found' => 'ABC'], $errors[0]['constraint']['params']);
    }
}
