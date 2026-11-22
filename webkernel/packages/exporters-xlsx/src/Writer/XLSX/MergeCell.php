<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
// Adapted from OpenSpout, XLSX writer components; see ../LICENSE.
namespace Webkernel\Exporters\Xlsx\Writer\XLSX;

/**
 * @internal
 */
final readonly class MergeCell
{
    /**
     * @param 0|positive-int $sheetIndex
     * @param 0|positive-int $topLeftColumn
     * @param positive-int   $topLeftRow
     * @param 0|positive-int $bottomRightColumn
     * @param positive-int   $bottomRightRow
     */
    public function __construct(
        public int $sheetIndex,
        public int $topLeftColumn,
        public int $topLeftRow,
        public int $bottomRightColumn,
        public int $bottomRightRow,
    ) {}
}
