<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
// Adapted from OpenSpout, XLSX writer components; see ../LICENSE.
namespace Webkernel\Exporters\Xlsx\Writer\Common\Manager\Style;

use Webkernel\Exporters\Xlsx\Common\Entity\Style\Style;

/**
 * @internal
 */
final readonly class PossiblyUpdatedStyle
{
    private Style $style;
    private bool $isUpdated;

    public function __construct(Style $style, bool $isUpdated)
    {
        $this->style = $style;
        $this->isUpdated = $isUpdated;
    }

    public function getStyle(): Style
    {
        return $this->style;
    }

    public function isUpdated(): bool
    {
        return $this->isUpdated;
    }
}
