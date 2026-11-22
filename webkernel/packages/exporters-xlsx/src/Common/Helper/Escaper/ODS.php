<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
// Adapted from OpenSpout, XLSX writer components; see ../LICENSE.
namespace Webkernel\Exporters\Xlsx\Common\Helper\Escaper;

/**
 * @internal
 */
final class ODS implements EscaperInterface
{
    /**
     * Escapes the given string to make it compatible with XLSX.
     *
     * @param string $string The string to escape
     *
     * @return string The escaped string
     */
    public function escape(string $string): string
    {
        /*
         * 'ENT_DISALLOWED' ensures that invalid characters in the given document type are replaced.
         * Otherwise control characters like a vertical tab "\v" will make the XML document unreadable by the XML processor.
         *
         * @see https://github.com/box/spout/issues/329
         */
        return htmlspecialchars($string, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8');
    }

    /**
     * Unescapes the given string to make it compatible with XLSX.
     *
     * @param string $string The string to unescape
     *
     * @return string The unescaped string
     */
    public function unescape(string $string): string
    {
        // ==============
        // =   WARNING  =
        // ==============
        // It is assumed that the given string has already had its XML entities decoded.
        // This is true if the string is coming from a DOMNode (as DOMNode already decode XML entities on creation).
        // Therefore there is no need to call "htmlspecialchars_decode()".
        return $string;
    }
}
