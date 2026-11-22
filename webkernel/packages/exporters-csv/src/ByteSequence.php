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

namespace Webkernel\Exporters\Csv;

use Deprecated;

/**
 * Defines constants for common BOM sequences.
 *
 * @deprecated since version 9.16.0
 * @see Bom
 */
interface ByteSequence
{
    #[Deprecated(message: 'use Webkernel\Exporters\Csv\Bom:Utf8 instead', since: 'league/csv:9.16.0')]
    public const BOM_UTF8 = "\xEF\xBB\xBF";
    #[Deprecated(message: 'use Webkernel\Exporters\Csv\Bom:Utf16be instead', since: 'league/csv:9.16.0')]
    public const BOM_UTF16_BE = "\xFE\xFF";
    #[Deprecated(message: 'use Webkernel\Exporters\Csv\Bom:Utf16Le instead', since: 'league/csv:9.16.0')]
    public const BOM_UTF16_LE = "\xFF\xFE";
    #[Deprecated(message: 'use Webkernel\Exporters\Csv\Bom:Utf32Be instead', since: 'league/csv:9.16.0')]
    public const BOM_UTF32_BE = "\x00\x00\xFE\xFF";
    #[Deprecated(message: 'use Webkernel\Exporters\Csv\Bom:Utf32Le instead', since: 'league/csv:9.16.0')]
    public const BOM_UTF32_LE = "\xFF\xFE\x00\x00";
}
