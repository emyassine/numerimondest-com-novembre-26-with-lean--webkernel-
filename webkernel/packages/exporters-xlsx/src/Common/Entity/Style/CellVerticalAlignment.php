<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
// Adapted from OpenSpout, XLSX writer components; see ../LICENSE.
namespace Webkernel\Exporters\Xlsx\Common\Entity\Style;

/**
 * This class provides constants to work with text vertical alignment.
 */
final class CellVerticalAlignment
{
    public const AUTO = 'auto';
    public const BASELINE = 'baseline';
    public const BOTTOM = 'bottom';
    public const CENTER = 'center';
    public const DISTRIBUTED = 'distributed';
    public const JUSTIFY = 'justify';
    public const TOP = 'top';

    private const VALID_ALIGNMENTS = [
        self::AUTO => 1,
        self::BASELINE => 1,
        self::BOTTOM => 1,
        self::CENTER => 1,
        self::DISTRIBUTED => 1,
        self::JUSTIFY => 1,
        self::TOP => 1,
    ];

    /**
     * @return bool Whether the given cell vertical alignment is valid
     */
    public static function isValid(string $cellVerticalAlignment): bool
    {
        return isset(self::VALID_ALIGNMENTS[$cellVerticalAlignment]);
    }
}
