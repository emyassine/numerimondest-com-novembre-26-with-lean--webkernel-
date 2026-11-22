<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Platform\Runtime;

/** Stable bit assignments for compiled panel visibility checks. */
enum Ability: int
{
    case ViewDashboard = 1;
    case ViewUsers = 2;
    case ManageUsers = 4;
    case ViewReports = 8;
    case ManageSettings = 16;

    /** @param list<self> $abilities */
    public static function mask(array $abilities): int
    {
        $mask = 0;
        foreach ($abilities as $ability) {
            $mask |= $ability->value;
        }

        return $mask;
    }
}
