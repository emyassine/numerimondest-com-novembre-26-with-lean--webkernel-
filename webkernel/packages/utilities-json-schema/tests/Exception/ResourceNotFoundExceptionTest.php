<?php

declare(strict_types=1);

namespace Webkernel\Utilities\JsonSchema\Tests\Exception;

use Webkernel\Utilities\JsonSchema\Exception\ResourceNotFoundException;
use PHPUnit\Framework\TestCase;

class ResourceNotFoundExceptionTest extends TestCase
{
    public function testHierarchy(): void
    {
        $exception = new ResourceNotFoundException();
        self::assertInstanceOf('\RuntimeException', $exception);
        self::assertInstanceOf(\Webkernel\Utilities\JsonSchema\Exception\RuntimeException::class, $exception);
        self::assertInstanceOf(\Webkernel\Utilities\JsonSchema\Exception\ExceptionInterface::class, $exception);
    }
}
