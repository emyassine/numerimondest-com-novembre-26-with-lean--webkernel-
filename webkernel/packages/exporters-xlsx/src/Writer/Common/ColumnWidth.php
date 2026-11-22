<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
// Adapted from OpenSpout, XLSX writer components; see ../LICENSE.
namespace Webkernel\Exporters\Xlsx\Writer\Common;

/**
 * @internal
 */
final readonly class ColumnWidth
{
    /**
     * @param positive-int $start
     * @param positive-int $end
     */
    public function __construct(
        public int $start,
        public int $end,
        public float $width,
    ) {}
}
