<?php

declare(strict_types=1);

namespace Webkernel\Utilities\JsonSchema\Tests\Exception;

use Webkernel\Utilities\JsonSchema\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class InvalidArgumentExceptionTest extends TestCase
{
    public function testHierarchy(): void
    {
        $exception = new InvalidArgumentException();
        self::assertInstanceOf('\InvalidArgumentException', $exception);
        self::assertInstanceOf(\Webkernel\Utilities\JsonSchema\Exception\ExceptionInterface::class, $exception);
    }
}
