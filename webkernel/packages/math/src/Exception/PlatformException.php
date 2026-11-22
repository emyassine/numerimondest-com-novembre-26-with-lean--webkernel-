<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Math\Exception;

use RuntimeException;

use function preg_last_error_msg;
use function sprintf;

use const PHP_INT_SIZE;

/**
 * Exception thrown when the current PHP platform does not support a required feature.
 */
final class PlatformException extends RuntimeException implements MathException
{
    /**
     * @internal
     *
     * @pure
     */
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

    /**
     * @internal
     *
     * @pure
     *
     * @codeCoverageIgnore
     */
    public static function unsupportedFloatFormat(): self
    {
        return new self('Unsupported float format: expected IEEE-754 double.');
    }

    /**
     * @internal
     *
     * @pure
     *
     * @codeCoverageIgnore
     */
    public static function unsupportedIntSize(): self
    {
        return new self(sprintf('Unsupported integer size: %d bytes, expected 4 or 8.', PHP_INT_SIZE));
    }

    /**
     * @internal
     *
     * @pure
     */
    public static function pcreFailure(): self
    {
        return new self(sprintf(
            'PCRE regular expression matching failed: %s. Check the pcre.* ini settings.',
            preg_last_error_msg(),
        ));
    }
}
