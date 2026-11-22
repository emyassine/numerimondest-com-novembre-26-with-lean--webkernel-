<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
// Adapted from OpenSpout, XLSX writer components; see ../LICENSE.
namespace Webkernel\Exporters\Xlsx\Writer\XLSX\Options;

final readonly class HeaderFooter
{
    public function __construct(
        public ?string $oddHeader = null,
        public ?string $oddFooter = null,
        public ?string $evenHeader = null,
        public ?string $evenFooter = null,
        public bool $differentOddEven = false,
    ) {}
}
