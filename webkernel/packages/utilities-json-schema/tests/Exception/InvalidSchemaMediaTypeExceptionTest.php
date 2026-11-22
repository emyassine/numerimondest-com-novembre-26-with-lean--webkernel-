<?php

declare(strict_types=1);

namespace Webkernel\Utilities\JsonSchema\Tests\Exception;

use Webkernel\Utilities\JsonSchema\Exception\InvalidSchemaMediaTypeException;
use PHPUnit\Framework\TestCase;

class InvalidSchemaMediaTypeExceptionTest extends TestCase
{
    public function testHierarchy(): void
    {
        $exception = new InvalidSchemaMediaTypeException();
        self::assertInstanceOf('\RuntimeException', $exception);
        self::assertInstanceOf(\Webkernel\Utilities\JsonSchema\Exception\RuntimeException::class, $exception);
        self::assertInstanceOf(\Webkernel\Utilities\JsonSchema\Exception\ExceptionInterface::class, $exception);
    }
}
