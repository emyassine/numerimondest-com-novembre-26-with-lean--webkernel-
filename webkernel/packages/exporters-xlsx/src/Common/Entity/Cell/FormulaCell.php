<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
// Adapted from OpenSpout, XLSX writer components; see ../LICENSE.
namespace Webkernel\Exporters\Xlsx\Common\Entity\Cell;

use DateInterval;
use DateTimeImmutable;
use Webkernel\Exporters\Xlsx\Common\Entity\Cell;
use Webkernel\Exporters\Xlsx\Common\Entity\Style\Style;

final class FormulaCell extends Cell
{
    public function __construct(
        private readonly string $value,
        ?Style $style,
        private readonly bool|DateInterval|DateTimeImmutable|float|int|string|null $computedValue = null,
    ) {
        parent::__construct($style);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getComputedValue(): bool|DateInterval|DateTimeImmutable|float|int|string|null
    {
        return $this->computedValue;
    }
}
