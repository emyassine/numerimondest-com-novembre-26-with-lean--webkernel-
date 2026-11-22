<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

/**
 * This file is part of the ramsey/uuid library
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@benramsey.com>
 * @license http://opensource.org/licenses/MIT MIT
 */

declare(strict_types=1);

namespace Webkernel\Identifiers\Uuid\Converter\Number;

use \Webkernel\Identifiers\Uuid\Converter\NumberConverterInterface;
use \Webkernel\Identifiers\Uuid\Math\CalculatorInterface;
use \Webkernel\Identifiers\Uuid\Type\Integer as IntegerObject;

/**
 * GenericNumberConverter uses the provided calculator to convert decimal numbers to and from hexadecimal values
 *
 * @immutable
 */
class GenericNumberConverter implements NumberConverterInterface
{
    public function __construct(private CalculatorInterface $calculator)
    {
    }

    /**
     * @pure
     */
    public function fromHex(string $hex): string
    {
        /** @var non-empty-string $hex */
        return $this->calculator->fromBase($hex, 16)->toString();
    }

    /**
     * @pure
     */
    public function toHex(string $number): string
    {
        /** @phpstan-ignore return.type, possiblyImpure.new */
        return $this->calculator->toBase(new IntegerObject($number), 16);
    }
}
