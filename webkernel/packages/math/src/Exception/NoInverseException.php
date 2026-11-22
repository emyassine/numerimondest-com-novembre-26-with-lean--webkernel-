<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Math\Exception;

use RuntimeException;

/**
 * Exception thrown when attempting to compute a modular inverse that does not exist.
 */
final class NoInverseException extends RuntimeException implements MathException
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
    public static function noModularInverse(): self
    {
        return new self('This number has no multiplicative inverse modulo the given modulus (they are not coprime).');
    }
}
