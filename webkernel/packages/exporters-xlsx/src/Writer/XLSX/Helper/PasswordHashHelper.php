<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
// Adapted from OpenSpout, XLSX writer components; see ../LICENSE.
namespace Webkernel\Exporters\Xlsx\Writer\XLSX\Helper;

/**
 * @internal
 */
final class PasswordHashHelper
{
    public static function make(string $password): string
    {
        $verifier = 0;
        $pwlen = \strlen($password);
        $passwordArray = pack('c', $pwlen).$password;

        for ($i = $pwlen; $i >= 0; --$i) {
            $intermediate1 = (($verifier & 0x4000) === 0) ? 0 : 1;
            $intermediate2 = 2 * $verifier;
            $intermediate2 &= 0x7FFF;
            $intermediate3 = $intermediate1 | $intermediate2;
            $verifier = $intermediate3 ^ \ord($passwordArray[$i]);
        }

        $verifier ^= 0xCE4B;

        return strtoupper(dechex($verifier));
    }
}
