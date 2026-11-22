<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
// Adapted from OpenSpout, XLSX writer components; see ../LICENSE.
namespace Webkernel\Exporters\Xlsx\Writer\XLSX\Options;

final readonly class PageMargin
{
    public function __construct(
        public float $top = 0.75,
        public float $right = 0.7,
        public float $bottom = 0.75,
        public float $left = 0.7,
        public float $header = 0.3,
        public float $footer = 0.3
    ) {}
}
