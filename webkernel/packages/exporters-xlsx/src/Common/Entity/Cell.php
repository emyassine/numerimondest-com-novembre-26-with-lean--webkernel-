<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
// Adapted from OpenSpout, XLSX writer components; see ../LICENSE.
namespace Webkernel\Exporters\Xlsx\Common\Entity;

use DateInterval;
use DateTimeInterface;
use Webkernel\Exporters\Xlsx\Common\Entity\Cell\BooleanCell;
use Webkernel\Exporters\Xlsx\Common\Entity\Cell\DateIntervalCell;
use Webkernel\Exporters\Xlsx\Common\Entity\Cell\DateTimeCell;
use Webkernel\Exporters\Xlsx\Common\Entity\Cell\EmptyCell;
use Webkernel\Exporters\Xlsx\Common\Entity\Cell\FormulaCell;
use Webkernel\Exporters\Xlsx\Common\Entity\Cell\NumericCell;
use Webkernel\Exporters\Xlsx\Common\Entity\Cell\StringCell;
use Webkernel\Exporters\Xlsx\Common\Entity\Comment\Comment;
use Webkernel\Exporters\Xlsx\Common\Entity\Style\Style;

abstract class Cell
{
    public ?Comment $comment = null;

    private Style $style;

    public function __construct(?Style $style)
    {
        $this->setStyle($style);
    }

    abstract public function getValue(): bool|DateInterval|DateTimeInterface|float|int|string|null;

    final public function setStyle(?Style $style): void
    {
        $this->style = $style ?? new Style();
    }

    final public function getStyle(): Style
    {
        return $this->style;
    }

    final public static function fromValue(bool|DateInterval|DateTimeInterface|float|int|string|null $value, ?Style $style = null): self
    {
        if (\is_bool($value)) {
            return new BooleanCell($value, $style);
        }
        if (null === $value || '' === $value) {
            return new EmptyCell($value, $style);
        }
        if (\is_int($value) || \is_float($value)) {
            return new NumericCell($value, $style);
        }
        if ($value instanceof DateTimeInterface) {
            return new DateTimeCell($value, $style);
        }
        if ($value instanceof DateInterval) {
            return new DateIntervalCell($value, $style);
        }
        if (isset($value[0]) && '=' === $value[0]) {
            return new FormulaCell($value, $style, null);
        }

        return new StringCell($value, $style);
    }
}
