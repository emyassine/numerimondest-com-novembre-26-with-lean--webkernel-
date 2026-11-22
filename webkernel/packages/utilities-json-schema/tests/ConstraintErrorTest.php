<?php

declare(strict_types=1);

namespace Webkernel\Utilities\JsonSchema\Tests;

use Webkernel\Utilities\JsonSchema\ConstraintError;
use PHPUnit\Framework\TestCase;

class ConstraintErrorTest extends TestCase
{
    public function testGetValidMessage(): void
    {
        $e = ConstraintError::AllOf;
        $this->assertSame('Failed to match all schemas', $e->getMessage());
    }

    public function testGetInvalidMessage(): void
    {
        $e = ConstraintError::MissingError;

        $this->expectException(\Webkernel\Utilities\JsonSchema\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing error message for missingError');

        $e->getMessage();
    }
}
