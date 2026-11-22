<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Platform\Compiler;

/** Defines when a compiled template value is resolved. */
enum Resolution: string
{
    case Baked = 'baked';
    case PerRole = 'per_role';
    case PerRequest = 'per_request';
    case Legacy = 'legacy';
}
