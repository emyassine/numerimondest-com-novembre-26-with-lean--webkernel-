<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
// Adapted from League.Csv; see ../LICENSE.
/**
 * League.Csv (https://csv.thephpleague.com)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webkernel\Exporters\Csv\Query;

use Deprecated;
use Webkernel\Exporters\Csv\MapIterator;
use LimitIterator;

final class Limit
{
    public function __construct(
        public readonly int $offset,
        public readonly int $length,
    ) {
        0 <= $this->offset || throw new QueryException(self::class.' expects the offset to be greater or equal to 0, '.$this->offset.' given.');
        -2 < $this->length || throw new QueryException(self::class.' expects the length to be greater or equal to -1, '.$this->length.' given.');
    }

    /**
     * DEPRECATION WARNING! This method will be removed in the next major point release.
     *
     * @see Limit::__construct()
     * @deprecated Since version 9.25.0
     * @codeCoverageIgnore
     */
    #[Deprecated(message:'use Webkernel\Exporters\Csv\JsonConverter::__construct() instead', since:'league/csv:9.25.0')]
    public static function new(int $offset, int $length): self
    {
        return new self($offset, $length);
    }

    /**
     * Allows iteration over a limited subset of items in an iterable structure.
     */
    public function slice(iterable $value): LimitIterator
    {
        return new LimitIterator(MapIterator::toIterator($value), $this->offset, $this->length);
    }
}
