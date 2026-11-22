<?php

declare(strict_types=1);

namespace Webkernel\Utilities\JsonSchema\Tests\Exception;

use Webkernel\Utilities\JsonSchema\Exception\InvalidSourceUriException;
use PHPUnit\Framework\TestCase;

class InvalidSourceUriExceptionTest extends TestCase
{
    public function testHierarchy(): void
    {
        $exception = new InvalidSourceUriException();
        self::assertInstanceOf('\InvalidArgumentException', $exception);
        self::assertInstanceOf(\Webkernel\Utilities\JsonSchema\Exception\InvalidArgumentException::class, $exception);
        self::assertInstanceOf(\Webkernel\Utilities\JsonSchema\Exception\ExceptionInterface::class, $exception);
    }
}
