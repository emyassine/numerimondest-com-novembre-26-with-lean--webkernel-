<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
// Adapted from OpenSpout, XLSX writer components; see ../LICENSE.
namespace Webkernel\Exporters\Xlsx\Common\Entity\Style;

/**
 * This class provides constants to work with text alignment.
 */
final class CellAlignment
{
    public const LEFT = 'left';
    public const RIGHT = 'right';
    public const CENTER = 'center';
    public const JUSTIFY = 'justify';

    private const VALID_ALIGNMENTS = [
        self::LEFT => 1,
        self::RIGHT => 1,
        self::CENTER => 1,
        self::JUSTIFY => 1,
    ];

    /**
     * @return bool Whether the given cell alignment is valid
     */
    public static function isValid(string $cellAlignment): bool
    {
        return isset(self::VALID_ALIGNMENTS[$cellAlignment]);
    }
}
