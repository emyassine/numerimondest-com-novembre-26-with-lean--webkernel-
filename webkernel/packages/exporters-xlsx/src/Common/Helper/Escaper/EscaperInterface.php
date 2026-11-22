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
interface EscaperInterface
{
    /**
     * Escapes the given string to make it compatible with PHP.
     *
     * @param string $string The string to escape
     *
     * @return string The escaped string
     */
    public function escape(string $string): string;

    /**
     * Unescapes the given string to make it compatible with PHP.
     *
     * @param string $string The string to unescape
     *
     * @return string The unescaped string
     */
    public function unescape(string $string): string;
}
