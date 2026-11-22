<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when a division by zero occurs.
 */
final class DivisionByZeroException extends RuntimeException implements MathException
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
     */
    public static function divisionByZero(): self
    {
        return new self('Division by zero.');
    }

    /**
     * @internal
     *
     * @pure
     */
    public static function zeroModulus(): self
    {
        return new self('The modulus must not be zero.');
    }

    /**
     * @internal
     *
     * @pure
     */
    public static function zeroDenominator(): self
    {
        return new self('The denominator of a rational number must not be zero.');
    }

    /**
     * @internal
     *
     * @pure
     */
    public static function reciprocalOfZero(): self
    {
        return new self('The reciprocal of zero is undefined.');
    }

    /**
     * @internal
     *
     * @pure
     */
    public static function zeroToNegativePower(): self
    {
        return new self('Cannot raise zero to a negative power.');
    }
}
