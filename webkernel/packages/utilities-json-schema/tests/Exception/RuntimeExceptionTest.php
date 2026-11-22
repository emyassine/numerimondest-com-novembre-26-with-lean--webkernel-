<?php

declare(strict_types=1);

namespace Webkernel\Utilities\JsonSchema\Tests\Exception;

use Webkernel\Utilities\JsonSchema\Exception\RuntimeException;
use PHPUnit\Framework\TestCase;

class RuntimeExceptionTest extends TestCase
{
    public function testHierarchy(): void
    {
        $exception = new RuntimeException();
        self::assertInstanceOf('\RuntimeException', $exception);
        self::assertInstanceOf(\Webkernel\Utilities\JsonSchema\Exception\ExceptionInterface::class, $exception);
    }
}
